<?php
include "db.php";
require_once __DIR__ . "/includes/ui.php";

$message = "";
$message_type = "";

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
        $message_type = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
        $message_type = "error";
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

$customers_res = $conn->query("SELECT * FROM customer ORDER BY customer_id ASC");
$medicines_res = $conn->query("SELECT * FROM medicine ORDER BY medicine_id ASC");

render_app_shell_start('sale', 'Create New Sale', 'New Sale');
?>

<div class="editorial-hero">
    <div>
        <span class="editorial-kicker">
            <?php echo render_svg_icon('sparkles', 'icon-sm'); ?>
            <span>Point-of-Sale Workspace</span>
        </span>
        <h1 class="editorial-title">Dispense &amp; <span class="accent-word">Record</span> Pharmacy Sales.</h1>
        <p class="editorial-desc">Select an existing customer and medicine or register new records in one atomic MySQL transaction with live billing.</p>
    </div>

    <!-- Abstract Layered Organic Medical & Capsule Art (No Square Frame) -->
    <div class="editorial-art" aria-hidden="true">
        <svg viewBox="0 0 360 240" fill="none">
            <defs>
                <linearGradient id="blobGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#3B5BFF" stop-opacity="0.16"/>
                    <stop offset="55%" stop-color="#A78BFA" stop-opacity="0.18"/>
                    <stop offset="100%" stop-color="#14B8A6" stop-opacity="0.18"/>
                </linearGradient>
                <linearGradient id="capCobalt" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#3B5BFF"/>
                    <stop offset="100%" stop-color="#A78BFA"/>
                </linearGradient>
                <linearGradient id="capTeal" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#14B8A6"/>
                    <stop offset="100%" stop-color="#38BDF8"/>
                </linearGradient>
            </defs>

            <!-- Organic Flowing Backdrop Shape -->
            <path d="M54 118C44 64 98 22 174 28C250 34 318 68 310 132C302 196 232 222 154 212C76 202 64 172 54 118Z" fill="url(#blobGrad)"/>
            <circle cx="272" cy="54" r="36" fill="#FF7A6B" fill-opacity="0.14"/>
            <circle cx="84" cy="186" r="24" fill="#14B8A6" fill-opacity="0.16"/>

            <!-- Floating Abstract Capsules & Medical Cross -->
            <g class="drift-layer">
                <!-- Large Cobalt/White Capsule -->
                <g transform="translate(158, 42) rotate(32)">
                    <rect x="0" y="0" width="44" height="96" rx="22" fill="url(#capCobalt)"/>
                    <path d="M0 48 H44 V74 A22 22 0 0 1 0 74 Z" fill="#FFFFFF"/>
                    <rect x="11" y="14" width="6" height="22" rx="3" fill="#FFFFFF" opacity="0.45"/>
                </g>

                <!-- Teal/White Capsule -->
                <g transform="translate(242, 108) rotate(-28)">
                    <rect x="0" y="0" width="32" height="72" rx="16" fill="url(#capTeal)"/>
                    <path d="M0 36 H32 V56 A16 16 0 0 1 0 56 Z" fill="#FFFFFF"/>
                </g>

                <!-- Soft Coral Pill Ring -->
                <circle cx="104" cy="96" r="22" stroke="#FF7A6B" stroke-width="10" fill="#FAF9F6"/>

                <!-- Minimal Medical Cross -->
                <g transform="translate(194, 152)">
                    <rect x="0" y="0" width="46" height="46" rx="14" fill="#1E1B4B"/>
                    <rect x="19" y="11" width="8" height="24" rx="4" fill="#14B8A6"/>
                    <rect x="11" y="19" width="24" height="8" rx="4" fill="#14B8A6"/>
                </g>
            </g>
        </svg>
    </div>
</div>

<!-- 3-Stage Progress Tracker -->
<div class="progress-tracker" id="saleProgressTracker" aria-label="Sale Entry Progress">
    <div class="progress-step is-active" id="stepCustomer">
        <span class="progress-num">1</span>
        <div class="progress-meta">
            <strong>Customer</strong>
            <small id="stepCustomerStatus">Select or register</small>
        </div>
    </div>
    <div class="progress-step" id="stepMedicine">
        <span class="progress-num">2</span>
        <div class="progress-meta">
            <strong>Medicine</strong>
            <small id="stepMedicineStatus">Select or add SKU</small>
        </div>
    </div>
    <div class="progress-step" id="stepTransaction">
        <span class="progress-num">3</span>
        <div class="progress-meta">
            <strong>Transaction</strong>
            <small id="stepTransactionStatus">Quantity &amp; date</small>
        </div>
    </div>
</div>

<?php if (!empty($message)) : ?>
    <div class="alert-banner <?php echo $message_type; ?>" id="serverAlert"
         data-type="<?php echo $message_type; ?>"
         data-message="<?php echo htmlspecialchars($message); ?>">
        <?php echo render_svg_icon($message_type === 'success' ? 'check' : 'alert'); ?>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<form method="post" id="saleForm" novalidate>
    <div class="sale-workspace">
        <div class="sale-sections">
            <!-- Customer Section -->
            <section class="section-card customer-theme">
                <div class="card-head">
                    <div class="card-title-group">
                        <div class="card-icon blue"><?php echo render_svg_icon('user'); ?></div>
                        <div>
                            <h3>Customer Information</h3>
                            <p>Choose an existing customer or register a new profile</p>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="customer_select">Existing Customer</label>
                    <select name="customer_id" id="customer_select">
                        <option value="">Select Customer</option>
                        <?php while ($row = $customers_res->fetch_assoc()) : ?>
                            <option value="<?php echo $row['customer_id']; ?>">
                                <?php echo $row['customer_id'] . " - " . htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="or-divider">Or Register New Customer</div>

                <div class="fields-grid-2">
                    <div class="field">
                        <label for="new_cust_name">Customer Name</label>
                        <input type="text" name="new_cust_name" id="new_cust_name" placeholder="e.g. Kiran Raj">
                        <span class="live-badge" id="cust_id_preview"></span>
                    </div>

                    <div class="field">
                        <label for="new_phone">Phone Number</label>
                        <input type="text" name="new_phone" id="new_phone" placeholder="e.g. 9876501006">
                    </div>

                    <div class="field span-2">
                        <label for="new_address">Address</label>
                        <input type="text" name="new_address" id="new_address" placeholder="Street, City, State">
                    </div>
                </div>
            </section>

            <!-- Medicine Section -->
            <section class="section-card medicine-theme">
                <div class="card-head">
                    <div class="card-title-group">
                        <div class="card-icon teal"><?php echo render_svg_icon('pill'); ?></div>
                        <div>
                            <h3>Medicine Selection</h3>
                            <p>Auto-fills previous medicine or lets you add a new SKU</p>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="medicine_select">Existing Medicine</label>
                    <select name="medicine_id" id="medicine_select">
                        <option value="" data-price="0" data-name="Not selected">Select Medicine</option>
                        <?php while ($row = $medicines_res->fetch_assoc()) : ?>
                            <option value="<?php echo $row['medicine_id']; ?>"
                                    data-price="<?php echo $row['unit_price']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['medicine_name']); ?>">
                                <?php echo $row['medicine_id'] . " - " . htmlspecialchars($row['medicine_name']) . " (₹" . number_format($row['unit_price'], 2) . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <span class="live-badge" id="med_note"></span>
                </div>

                <div class="or-divider">Or Add New Medicine</div>

                <div class="fields-grid-3">
                    <div class="field">
                        <label for="new_med_name">Medicine Name</label>
                        <input type="text" name="new_med_name" id="new_med_name" placeholder="e.g. Azithromycin 500mg">
                        <span class="live-badge" id="med_id_preview"></span>
                    </div>

                    <div class="field">
                        <label for="new_manufacturer">Manufacturer</label>
                        <input type="text" name="new_manufacturer" id="new_manufacturer" placeholder="e.g. Cipla">
                    </div>

                    <div class="field">
                        <label for="new_unit_price">Unit Price (₹)</label>
                        <input type="number" step="0.01" name="new_unit_price" id="new_unit_price" min="0.01" placeholder="0.00">
                    </div>
                </div>
            </section>

            <!-- Sale Details Section -->
            <section class="section-card details-theme">
                <div class="card-head">
                    <div class="card-title-group">
                        <div class="card-icon purple"><?php echo render_svg_icon('calendar'); ?></div>
                        <div>
                            <h3>Transaction Details</h3>
                            <p>Set purchase quantity and billing date</p>
                        </div>
                    </div>
                </div>

                <div class="fields-grid-2">
                    <div class="field">
                        <label for="quantity_input">Quantity (Units)</label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" id="qtyMinusBtn" aria-label="Decrease quantity">-</button>
                            <input type="number" name="quantity" id="quantity_input" min="1" value="1" required>
                            <button type="button" class="qty-btn" id="qtyPlusBtn" aria-label="Increase quantity">+</button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="sale_date_input">Sale Date</label>
                        <input type="date" name="sale_date" id="sale_date_input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky Purchase Summary -->
        <aside class="summary-card" aria-label="Purchase Summary">
            <div class="summary-banner">
                <small>Live Checkout</small>
                <h3>Purchase Summary</h3>
            </div>
            <div class="summary-body">
                <div class="summary-row">
                    <span>Customer</span>
                    <strong id="summaryCustomer">Not selected</strong>
                </div>
                <div class="summary-row">
                    <span>Medicine</span>
                    <strong id="summaryMedicine">Not selected</strong>
                </div>
                <div class="summary-row">
                    <span>Unit Price</span>
                    <strong id="summaryPrice">₹0.00</strong>
                </div>
                <div class="summary-row">
                    <span>Quantity</span>
                    <strong id="summaryQty">1</strong>
                </div>
                <div class="summary-row">
                    <span>Sale Date</span>
                    <strong id="summaryDate"><?php echo date('Y-m-d'); ?></strong>
                </div>

                <div class="summary-total-box">
                    <span>Total Amount</span>
                    <strong id="live_total">₹0.00</strong>
                </div>

                <div class="summary-actions">
                    <button type="submit" name="submit" value="1" class="btn btn-primary" id="submitBtn">
                        <span class="btn-spinner"></span>
                        <?php echo render_svg_icon('check', 'icon-sm'); ?>
                        <span>Record Sale</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="clearFormBtn">
                        <?php echo render_svg_icon('rotate-ccw', 'icon-sm'); ?>
                        <span>Clear Form</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</form>

<script>
const customerMedMap = <?php echo json_encode($cust_med_map, JSON_FORCE_OBJECT); ?>;
const nextCustId     = <?php echo $next_cust_id; ?>;
const nextMedId      = <?php echo $next_med_id; ?>;

const saleForm       = document.getElementById("saleForm");
const custSelect     = document.getElementById("customer_select");
const medSelect      = document.getElementById("medicine_select");
const medNote        = document.getElementById("med_note");
const newCustName    = document.getElementById("new_cust_name");
const newAddress     = document.getElementById("new_address");
const newPhone       = document.getElementById("new_phone");
const custIdPreview  = document.getElementById("cust_id_preview");
const newMedName     = document.getElementById("new_med_name");
const newMfg         = document.getElementById("new_manufacturer");
const newPriceInput  = document.getElementById("new_unit_price");
const medIdPreview   = document.getElementById("med_id_preview");
const qtyInput       = document.getElementById("quantity_input");
const dateInput      = document.getElementById("sale_date_input");

const summaryCustomer = document.getElementById("summaryCustomer");
const summaryMedicine = document.getElementById("summaryMedicine");
const summaryPrice    = document.getElementById("summaryPrice");
const summaryQty      = document.getElementById("summaryQty");
const summaryDate     = document.getElementById("summaryDate");
const liveTotalEl     = document.getElementById("live_total");
const submitBtn       = document.getElementById("submitBtn");
const clearFormBtn    = document.getElementById("clearFormBtn");

function clearErrors() {
    saleForm.querySelectorAll(".input-error").forEach((el) => el.classList.remove("input-error"));
}

const stepCustomer          = document.getElementById("stepCustomer");
const stepMedicine          = document.getElementById("stepMedicine");
const stepTransaction       = document.getElementById("stepTransaction");
const stepCustomerStatus    = document.getElementById("stepCustomerStatus");
const stepMedicineStatus    = document.getElementById("stepMedicineStatus");
const stepTransactionStatus = document.getElementById("stepTransactionStatus");
const qtyMinusBtn           = document.getElementById("qtyMinusBtn");
const qtyPlusBtn            = document.getElementById("qtyPlusBtn");

let lastTotalText = "";

function updateProgressTracker() {
    const custDone = Boolean(custSelect.value) ||
        (newCustName.value.trim().length > 0 && newAddress.value.trim().length > 0 && newPhone.value.trim().length > 0);
    const medDone = Boolean(medSelect.value) ||
        (newMedName.value.trim().length > 0 && newMfg.value.trim().length > 0 && (parseFloat(newPriceInput.value) || 0) > 0);
    const txDone = (parseInt(qtyInput.value, 10) || 0) >= 1 && Boolean(dateInput.value);

    stepCustomer.classList.toggle("is-complete", custDone);
    stepCustomer.classList.toggle("is-active", !custDone);
    stepCustomerStatus.textContent = custDone ? "Ready" : "Select or register";

    stepMedicine.classList.toggle("is-complete", medDone);
    stepMedicine.classList.toggle("is-active", custDone && !medDone);
    stepMedicineStatus.textContent = medDone ? "Ready" : "Select or add SKU";

    stepTransaction.classList.toggle("is-complete", custDone && medDone && txDone);
    stepTransaction.classList.toggle("is-active", custDone && medDone);
    stepTransactionStatus.textContent = txDone ? "Ready to record" : "Quantity & date";
}

function updatePurchaseSummary() {
    const custText = newCustName.value.trim()
        ? newCustName.value.trim() + " (ID: " + nextCustId + ")"
        : (custSelect.value ? custSelect.options[custSelect.selectedIndex].text : "Not selected");

    let medText = "Not selected";
    let price = 0;

    if (newMedName.value.trim().length > 0) {
        medText = newMedName.value.trim() + " (ID: " + nextMedId + ")";
        price = parseFloat(newPriceInput.value) || 0;
    } else if (medSelect.value && medSelect.selectedIndex >= 0) {
        medText = medSelect.options[medSelect.selectedIndex].dataset.name || medSelect.options[medSelect.selectedIndex].text;
        price = parseFloat(medSelect.options[medSelect.selectedIndex].dataset.price) || 0;
    }

    const qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
    const totalStr = "₹" + (price * qty).toFixed(2);

    summaryCustomer.textContent = custText;
    summaryCustomer.classList.toggle("is-highlighted", custText !== "Not selected");
    summaryMedicine.textContent = medText;
    summaryMedicine.classList.toggle("is-highlighted", medText !== "Not selected");
    summaryPrice.textContent    = "₹" + price.toFixed(2);
    summaryQty.textContent      = String(qty);
    summaryDate.textContent     = dateInput.value || "Not set";

    if (totalStr !== lastTotalText) {
        liveTotalEl.textContent = totalStr;
        liveTotalEl.classList.remove("is-bumped");
        void liveTotalEl.offsetWidth;
        liveTotalEl.classList.add("is-bumped");
        lastTotalText = totalStr;
    }

    updateProgressTracker();
}

qtyMinusBtn.addEventListener("click", function () {
    const cur = parseInt(qtyInput.value, 10) || 1;
    qtyInput.value = String(Math.max(1, cur - 1));
    clearErrors();
    updatePurchaseSummary();
});

qtyPlusBtn.addEventListener("click", function () {
    const cur = parseInt(qtyInput.value, 10) || 1;
    qtyInput.value = String(cur + 1);
    clearErrors();
    updatePurchaseSummary();
});

qtyInput.addEventListener("blur", function () {
    if ((parseInt(this.value, 10) || 0) < 1) {
        this.value = "1";
        updatePurchaseSummary();
    }
});

// Auto-select customer's medicine when customer name is selected from dropdown
custSelect.addEventListener("change", function () {
    clearErrors();
    const cid = String(this.value);
    if (cid) {
        newCustName.value = "";
        newAddress.value = "";
        newPhone.value = "";
        custIdPreview.textContent = "";
        if (customerMedMap[cid]) {
            medSelect.value = String(customerMedMap[cid]);
            newMedName.value = "";
            newMfg.value = "";
            newPriceInput.value = "";
            medIdPreview.textContent = "";
            medNote.textContent = "Auto-selected customer's last medicine";
        } else {
            medSelect.value = "";
            medNote.textContent = "";
        }
    } else {
        medSelect.value = "";
        medNote.textContent = "";
    }
    updatePurchaseSummary();
});

// Auto-generate ascending Customer ID preview when typing customer name
newCustName.addEventListener("input", function () {
    clearErrors();
    if (this.value.trim().length > 0) {
        custSelect.value = "";
        custIdPreview.textContent = "Auto Customer ID: #" + nextCustId;
    } else {
        custIdPreview.textContent = "";
    }
    updatePurchaseSummary();
});

// Auto-generate ascending Medicine ID preview when typing medicine name
newMedName.addEventListener("input", function () {
    clearErrors();
    if (this.value.trim().length > 0) {
        medSelect.value = "";
        medNote.textContent = "";
        medIdPreview.textContent = "Auto Medicine ID: #" + nextMedId;
    } else {
        medIdPreview.textContent = "";
    }
    updatePurchaseSummary();
});

medSelect.addEventListener("change", function () {
    clearErrors();
    if (this.value) {
        newMedName.value = "";
        newMfg.value = "";
        newPriceInput.value = "";
        medIdPreview.textContent = "";
    }
    updatePurchaseSummary();
});

[newAddress, newPhone, newMfg, newPriceInput, qtyInput, dateInput].forEach((el) => {
    el.addEventListener("input", () => {
        clearErrors();
        updatePurchaseSummary();
    });
});

// Client-side validation, scroll to first invalid field & loading feedback
saleForm.addEventListener("submit", function (e) {
    clearErrors();
    const errors = [];
    let firstInvalid = null;

    function flagInvalid(el, msg) {
        el.classList.add("input-error");
        if (!firstInvalid) firstInvalid = el;
        errors.push(msg);
    }

    if (newCustName.value.trim().length > 0) {
        if (!newAddress.value.trim()) flagInvalid(newAddress, "Customer address is required.");
        if (!newPhone.value.trim())   flagInvalid(newPhone, "Customer phone is required.");
    } else if (!custSelect.value) {
        flagInvalid(custSelect, "Please select an existing customer or enter a new customer.");
    }

    if (newMedName.value.trim().length > 0) {
        if (!newMfg.value.trim()) flagInvalid(newMfg, "Medicine manufacturer is required.");
        if ((parseFloat(newPriceInput.value) || 0) <= 0) flagInvalid(newPriceInput, "Enter a valid unit price (> 0).");
    } else if (!medSelect.value) {
        flagInvalid(medSelect, "Please select an existing medicine or add a new medicine.");
    }

    if ((parseInt(qtyInput.value, 10) || 0) < 1) {
        flagInvalid(qtyInput, "Quantity must be at least 1.");
    }

    if (!dateInput.value) {
        flagInvalid(dateInput, "Please select a sale date.");
    }

    if (errors.length > 0) {
        e.preventDefault();
        if (firstInvalid) {
            const reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            firstInvalid.scrollIntoView({ behavior: reduced ? "auto" : "smooth", block: "center" });
            firstInvalid.focus({ preventScroll: true });
        }
        if (typeof window.showToast === "function") {
            window.showToast("Check Form Fields", errors[0], "error");
        }
        return;
    }

    submitBtn.classList.add("is-loading");
});

// Clear form with confirmation if user entered data
clearFormBtn.addEventListener("click", function () {
    const hasInput = custSelect.value || medSelect.value || newCustName.value.trim() || newMedName.value.trim();
    if (hasInput && !confirm("Clear all entered sale details?")) {
        return;
    }
    saleForm.reset();
    clearErrors();
    custIdPreview.textContent = "";
    medIdPreview.textContent  = "";
    medNote.textContent       = "";
    qtyInput.value            = "1";
    dateInput.value           = "<?php echo date('Y-m-d'); ?>";
    updatePurchaseSummary();
});

updatePurchaseSummary();
</script>

<?php render_app_shell_end(); ?>
