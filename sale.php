<?php
include "db.php";

$message = "";

if (isset($_POST['submit'])) {
    $conn->begin_transaction();

    try {
        $new_cust_name = trim($_POST['new_cust_name'] ?? '');
        $new_med_name  = trim($_POST['new_med_name'] ?? '');

        // 1. Resolve Customer (New Customer vs Existing Dropdown)
        if (!empty($new_cust_name)) {
            $new_address = trim($_POST['new_address'] ?? '');
            $new_phone   = trim($_POST['new_phone'] ?? '');

            if (empty($new_address) || empty($new_phone)) {
                throw new Exception("Please enter Address and Phone for the new customer.");
            }

            $stmt_c = $conn->prepare("INSERT INTO customer (name, address, phone) VALUES (?, ?, ?)");
            $stmt_c->bind_param("sss", $new_cust_name, $new_address, $new_phone);
            if (!$stmt_c->execute()) {
                throw new Exception("Error adding new customer.");
            }
            $customer_id = $conn->insert_id;
            $stmt_c->close();
        } else {
            $customer_id = intval($_POST['customer_id'] ?? 0);
        }

        // 2. Resolve Medicine (New Medicine vs Existing Dropdown)
        if (!empty($new_med_name)) {
            $new_manufacturer = trim($_POST['new_manufacturer'] ?? '');
            $new_unit_price   = floatval($_POST['new_unit_price'] ?? 0);

            if (empty($new_manufacturer) || $new_unit_price <= 0) {
                throw new Exception("Please enter Manufacturer and valid Unit Price for the new medicine.");
            }

            $stmt_m = $conn->prepare("INSERT INTO medicine (medicine_name, manufacturer, unit_price) VALUES (?, ?, ?)");
            $stmt_m->bind_param("ssd", $new_med_name, $new_manufacturer, $new_unit_price);
            if (!$stmt_m->execute()) {
                throw new Exception("Error adding new medicine.");
            }
            $medicine_id = $conn->insert_id;
            $stmt_m->close();
        } else {
            $medicine_id = intval($_POST['medicine_id'] ?? 0);
        }

        $quantity  = intval($_POST['quantity'] ?? 0);
        $sale_date = $_POST['sale_date'] ?? '';

        if (empty($customer_id) || empty($medicine_id) || $quantity < 1 || empty($sale_date)) {
            throw new Exception("Please select or enter all required Customer, Medicine, Quantity, and Sale Date details.");
        }

        // 3. Insert into Sale Table
        $sql = "INSERT INTO sale (customer_id, medicine_id, quantity, sale_date) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $customer_id, $medicine_id, $quantity, $sale_date);

        if (!$stmt->execute()) {
            throw new Exception("Error: Record could not be saved.");
        }
        $stmt->close();

        $conn->commit();
        $message = "Record saved successfully! (Customer ID: $customer_id, Medicine ID: $medicine_id)";
    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
}

// Next ascending IDs for live preview
$next_cust_row = $conn->query("SELECT COALESCE(MAX(customer_id), 0) + 1 AS next_id FROM customer")->fetch_assoc();
$next_cust_id  = $next_cust_row['next_id'];

$next_med_row  = $conn->query("SELECT COALESCE(MAX(medicine_id), 0) + 1 AS next_id FROM medicine")->fetch_assoc();
$next_med_id   = $next_med_row['next_id'];

// Map every customer_id to their associated/last purchased medicine_id
$cust_med_map = [];
$map_res = $conn->query("SELECT c.customer_id, COALESCE(s_med.medicine_id, id_med.medicine_id) AS mapped_med_id
    FROM customer c
    LEFT JOIN (
        SELECT customer_id, medicine_id FROM sale WHERE sale_id IN (SELECT MAX(sale_id) FROM sale GROUP BY customer_id)
    ) s_med ON c.customer_id = s_med.customer_id
    LEFT JOIN medicine id_med ON c.customer_id = id_med.medicine_id");
while ($m = $map_res->fetch_assoc()) {
    if (!empty($m['mapped_med_id'])) {
        $cust_med_map[strval($m['customer_id'])] = strval($m['mapped_med_id']);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Sales</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>
<div class="container">

    <header class="app-header">
        <div class="brand">
            <div class="brand-icon">+</div>
            <div>
                <h2>PharmaCare Sales System</h2>
                <p class="brand-sub">Point-of-Sale &amp; Inventory Entry</p>
            </div>
        </div>
        <nav class="nav-tabs">
            <a class="nav-link active" href="sale.php">New Sale</a>
            <a class="nav-link" href="report.php">Sales Report</a>
        </nav>
    </header>

    <p class="message"><?php echo htmlspecialchars($message); ?></p>

    <form method="post" class="sale-form">
        <div class="form-grid">

            <div class="section-card">
                <h3><span class="step-badge">1</span> Select Existing Customer &amp; Medicine</h3>
                <div class="field">
                    <label for="customer_select">Customer</label>
                    <select name="customer_id" id="customer_select">
                        <option value="">Select Customer</option>

                        <?php
                        $result = $conn->query("SELECT * FROM customer ORDER BY customer_id ASC");

                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['customer_id'] . "'>"
                                . $row['customer_id'] . " - " . htmlspecialchars($row['name']) .
                                "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="field">
                    <label for="medicine_select">Medicine</label>
                    <select name="medicine_id" id="medicine_select">
                        <option value="" data-price="0">Select Medicine</option>

                        <?php
                        $result = $conn->query("SELECT * FROM medicine ORDER BY medicine_id ASC");

                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['medicine_id'] . "' data-price='" . $row['unit_price'] . "'>"
                                . $row['medicine_id'] . " - " . htmlspecialchars($row['medicine_name']) . " (₹" . number_format($row['unit_price'], 2) . ")" .
                                "</option>";
                        }
                        ?>
                    </select>
                    <span id="med_note"></span>
                </div>
            </div>

            <div class="section-card">
                <h3><span class="step-badge">2</span> Or Add New Customer &amp; Medicine</h3>
                <div class="sub-grid">
                    <div class="field">
                        <label for="new_cust_name">Customer Name</label>
                        <input type="text" name="new_cust_name" id="new_cust_name" placeholder="e.g. Kiran Raj">
                        <span id="cust_id_preview"></span>
                    </div>

                    <div class="field">
                        <label for="new_phone">Phone</label>
                        <input type="text" name="new_phone" id="new_phone" placeholder="10-digit number">
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="new_address">Address</label>
                        <input type="text" name="new_address" id="new_address" placeholder="City / Street address">
                    </div>

                    <div class="field">
                        <label for="new_med_name">Medicine Name</label>
                        <input type="text" name="new_med_name" id="new_med_name" placeholder="e.g. Azithromycin 500mg">
                        <span id="med_id_preview"></span>
                    </div>

                    <div class="field">
                        <label for="new_manufacturer">Manufacturer</label>
                        <input type="text" name="new_manufacturer" id="new_manufacturer" placeholder="e.g. Cipla">
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="new_unit_price">Unit Price (₹)</label>
                        <input type="number" step="0.01" name="new_unit_price" id="new_unit_price" min="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="section-card full-width">
                <h3><span class="step-badge">3</span> Sale Details</h3>
                <div class="sub-grid">
                    <div class="field">
                        <label for="quantity_input">Quantity</label>
                        <input type="number" name="quantity" id="quantity_input" min="1" value="1" required>
                    </div>

                    <div class="field">
                        <label for="sale_date_input">Sale Date</label>
                        <input type="date" name="sale_date" id="sale_date_input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="checkout-bar">
                    <div class="bill-preview">
                        <span class="bill-label">Estimated Total:</span>
                        <span class="bill-amount" id="live_total">₹0.00</span>
                    </div>
                    <input type="submit" name="submit" value="Record Sale">
                </div>
            </div>

        </div>
    </form>

    <script>
    const customerMedMap = <?php echo json_encode($cust_med_map, JSON_FORCE_OBJECT); ?>;
    const nextCustId     = <?php echo $next_cust_id; ?>;
    const nextMedId      = <?php echo $next_med_id; ?>;

    const custSelect     = document.getElementById("customer_select");
    const medSelect      = document.getElementById("medicine_select");
    const medNote        = document.getElementById("med_note");
    const newCustName    = document.getElementById("new_cust_name");
    const custIdPreview  = document.getElementById("cust_id_preview");
    const newMedName     = document.getElementById("new_med_name");
    const medIdPreview   = document.getElementById("med_id_preview");

    // Auto-select customer's medicine when customer name is selected from dropdown
    custSelect.addEventListener("change", function() {
        const cid = String(this.value);
        if (cid) {
            newCustName.value = "";
            custIdPreview.textContent = "";
            if (customerMedMap[cid]) {
                medSelect.value = String(customerMedMap[cid]);
                newMedName.value = "";
                medIdPreview.textContent = "";
                medNote.textContent = " (Auto-selected customer's medicine)";
            } else {
                medSelect.value = "";
                medNote.textContent = "";
            }
        } else {
            medSelect.value = "";
            medNote.textContent = "";
        }
    });

    // Auto-generate ascending Customer ID preview when typing customer name
    newCustName.addEventListener("input", function() {
        if (this.value.trim().length > 0) {
            custSelect.value = "";
            custIdPreview.textContent = " (Auto Customer ID: " + nextCustId + ")";
        } else {
            custIdPreview.textContent = "";
        }
    });

    // Auto-generate ascending Medicine ID preview when typing medicine name
    newMedName.addEventListener("input", function() {
        if (this.value.trim().length > 0) {
            medSelect.value = "";
            medNote.textContent = "";
            medIdPreview.textContent = " (Auto Medicine ID: " + nextMedId + ")";
        } else {
            medIdPreview.textContent = "";
        }
    });

    medSelect.addEventListener("change", function() {
        if (this.value) {
            newMedName.value = "";
            medIdPreview.textContent = "";
        }
        updateLiveTotal();
    });

    const qtyInput      = document.getElementById("quantity_input");
    const newPriceInput = document.getElementById("new_unit_price");
    const liveTotalEl   = document.getElementById("live_total");

    function updateLiveTotal() {
        const qty = parseInt(qtyInput.value, 10) || 0;
        let price = 0;
        if (newMedName.value.trim().length > 0) {
            price = parseFloat(newPriceInput.value) || 0;
        } else if (medSelect.selectedIndex >= 0) {
            price = parseFloat(medSelect.options[medSelect.selectedIndex].dataset.price) || 0;
        }
        liveTotalEl.textContent = "₹" + (price * qty).toFixed(2);
    }

    custSelect.addEventListener("change", updateLiveTotal);
    newMedName.addEventListener("input", updateLiveTotal);
    newPriceInput.addEventListener("input", updateLiveTotal);
    qtyInput.addEventListener("input", updateLiveTotal);
    updateLiveTotal();
    </script>

</div>
</body>
</html>
