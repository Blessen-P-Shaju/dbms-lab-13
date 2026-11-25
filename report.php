<?php
include "db.php";

$selected_customer = isset($_GET['customer_id']) ? $_GET['customer_id'] : 'all';
$from_date         = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date           = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

$conditions = [];
$params     = [];
$types      = "";

if ($selected_customer !== 'all' && is_numeric($selected_customer)) {
    $conditions[] = "c.customer_id = ?";
    $params[]     = intval($selected_customer);
    $types       .= "i";
}
if (!empty($from_date)) {
    $conditions[] = "s.sale_date >= ?";
    $params[]     = $from_date;
    $types       .= "s";
}
if (!empty($to_date)) {
    $conditions[] = "s.sale_date <= ?";
    $params[]     = $to_date;
    $types       .= "s";
}

$where_sql = count($conditions) > 0 ? " WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT s.sale_id,
               c.customer_id,
               c.name AS customer_name,
               c.address,
               c.phone,
               m.medicine_name,
               m.manufacturer,
               s.quantity,
               (m.unit_price * s.quantity) AS amount,
               s.sale_date
        FROM sale s
        INNER JOIN customer c ON s.customer_id = c.customer_id
        INNER JOIN medicine m ON s.medicine_id = m.medicine_id"
        . $where_sql .
        " ORDER BY s.sale_id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$total_sales_count = 0;
$total_units = 0;
$total_revenue = 0.0;
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $total_sales_count++;
        $total_units += intval($r['quantity']);
        $total_revenue += floatval($r['amount']);
    }
}

$customers = $conn->query("SELECT customer_id, name FROM customer ORDER BY customer_id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>
<div class="container container-wide">

    <header class="app-header">
        <div class="brand">
            <div class="brand-icon">+</div>
            <div>
                <h2>PharmaCare Sales Report</h2>
                <p class="brand-sub">Transaction Analytics &amp; History</p>
            </div>
        </div>
        <nav class="nav-tabs">
            <a class="nav-link" href="sale.php">New Sale</a>
            <a class="nav-link active" href="report.php">Sales Report</a>
        </nav>
    </header>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Transactions</div>
            <div class="kpi-value"><?php echo $total_sales_count; ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Units Sold</div>
            <div class="kpi-value"><?php echo $total_units; ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">₹<?php echo number_format($total_revenue, 2); ?></div>
        </div>
    </div>

    <form method="get" action="" class="filter-bar">
        Select Report:
        <select name="customer_id" onchange="this.form.submit()">
            <option value="all" <?php if ($selected_customer === 'all') echo 'selected'; ?>>All Customers Report</option>
            <?php while ($c = $customers->fetch_assoc()) { ?>
                <option value="<?php echo $c['customer_id']; ?>" <?php if ($selected_customer == $c['customer_id']) echo 'selected'; ?>>
                    <?php echo $c['customer_id'] . " - " . htmlspecialchars($c['name']); ?>
                </option>
            <?php } ?>
        </select>
        From: <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
        To: <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
        <input type="submit" value="Filter">
        <a class="reset-link" href="report.php">Reset</a>
    </form>

    <div class="search-bar">
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by customer, medicine, manufacturer, or date...">
    </div>

    <div class="table-wrapper">
    <table border="1" cellpadding="8" cellspacing="0" id="reportTable">
        <tr>
            <th>Sale ID</th>
            <th>Customer ID</th>
            <th>Customer Name</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Medicine Name</th>
            <th>Manufacturer</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Sale Date</th>
        </tr>

        <?php
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td>" . $row['sale_id'] . "</td>";
                echo "<td>" . $row['customer_id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['manufacturer']) . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "<td>₹" . number_format((float)$row['amount'], 2) . "</td>";
                echo "<td>" . $row['sale_date'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='10'>No records found</td></tr>";
        }
        ?>
    </table>
    </div>

    <script>
    function filterTable() {
        const query = document.getElementById("searchInput").value.toLowerCase();
        const rows = document.querySelectorAll("#reportTable tr");
        for (let i = 1; i < rows.length; i++) {
            rows[i].style.display = rows[i].textContent.toLowerCase().includes(query) ? "" : "none";
        }
    }
    </script>

</div>
</body>
</html>
