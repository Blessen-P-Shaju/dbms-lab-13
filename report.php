<?php
include "db.php";
require_once __DIR__ . "/includes/ui.php";

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
$total_transactions = 0;
$total_units        = 0;
$total_revenue      = 0.0;
$medicine_totals    = [];

if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $total_transactions++;
        $total_units   += intval($r['quantity']);
        $amt            = floatval($r['amount']);
        $total_revenue += $amt;

        $med_key = $r['medicine_name'];
        if (!isset($medicine_totals[$med_key])) {
            $medicine_totals[$med_key] = 0.0;
        }
        $medicine_totals[$med_key] += $amt;
    }
}

arsort($medicine_totals);
$max_med_revenue = count($medicine_totals) > 0 ? max($medicine_totals) : 1;

$total_customers_row = $conn->query("SELECT COUNT(*) AS cnt FROM customer")->fetch_assoc();
$total_customers     = intval($total_customers_row['cnt'] ?? 0);

$customers = $conn->query("SELECT customer_id, name FROM customer ORDER BY customer_id ASC");

render_app_shell_start('report', 'Sales Reports', 'Sales Reports');
?>

<div class="page-header">
    <div>
        <h1>Sales Analytics &amp; Reports</h1>
        <p class="page-subtitle">Real-time transaction metrics, revenue breakdown, and filterable sales history.</p>
    </div>
    <a href="sale.php" class="btn btn-primary">
        <?php echo render_svg_icon('plus-circle', 'icon-sm'); ?>
        <span>New Sale</span>
    </a>
</div>

<!-- KPI Summary Strip -->
<section class="kpi-grid" aria-label="Sales Summary">
    <div class="kpi-card">
        <div class="kpi-info">
            <span>Total Transactions</span>
            <strong data-countup="<?php echo $total_transactions; ?>" data-decimals="0"><?php echo $total_transactions; ?></strong>
        </div>
        <div class="kpi-icon blue"><?php echo render_svg_icon('receipt'); ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-info">
            <span>Total Revenue</span>
            <strong data-countup="<?php echo number_format($total_revenue, 2, '.', ''); ?>" data-prefix="₹" data-decimals="2">₹<?php echo number_format($total_revenue, 2); ?></strong>
        </div>
        <div class="kpi-icon teal"><?php echo render_svg_icon('wallet'); ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-info">
            <span>Total Customers</span>
            <strong data-countup="<?php echo $total_customers; ?>" data-decimals="0"><?php echo $total_customers; ?></strong>
        </div>
        <div class="kpi-icon purple"><?php echo render_svg_icon('users'); ?></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-info">
            <span>Units Sold</span>
            <strong data-countup="<?php echo $total_units; ?>" data-decimals="0"><?php echo $total_units; ?></strong>
        </div>
        <div class="kpi-icon amber"><?php echo render_svg_icon('package'); ?></div>
    </div>
</section>

<!-- Real-Data Sales Overview Chart -->
<?php if (count($medicine_totals) > 0) : ?>
<section class="chart-card" aria-label="Revenue by Medicine">
    <div class="chart-header">
        <h3>Revenue by Medicine</h3>
        <span>Calculated from matching database records</span>
    </div>
    <div class="chart-bars">
        <?php foreach (array_slice($medicine_totals, 0, 6, true) as $medName => $medRev) :
            $pct = $max_med_revenue > 0 ? round(($medRev / $max_med_revenue) * 100, 1) : 0;
        ?>
            <div class="chart-bar-row">
                <div class="chart-bar-label" title="<?php echo htmlspecialchars($medName); ?>">
                    <?php echo htmlspecialchars($medName); ?>
                </div>
                <div class="chart-bar-track">
                    <div class="chart-bar-fill" data-width="<?php echo $pct; ?>"></div>
                </div>
                <div class="chart-bar-value">₹<?php echo number_format($medRev, 2); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Filters & Sales Table -->
<section class="report-card">
    <div class="filter-toolbar">
        <form method="get" action="" class="filter-form">
            <div class="filter-group">
                <label for="customerFilter">Customer</label>
                <select name="customer_id" id="customerFilter" onchange="this.form.submit()">
                    <option value="all" <?php if ($selected_customer === 'all') echo 'selected'; ?>>All Customers Report</option>
                    <?php while ($c = $customers->fetch_assoc()) : ?>
                        <option value="<?php echo $c['customer_id']; ?>" <?php if ($selected_customer == $c['customer_id']) echo 'selected'; ?>>
                            <?php echo $c['customer_id'] . " - " . htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="fromDate">From Date</label>
                <input type="date" id="fromDate" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
            </div>

            <div class="filter-group">
                <label for="toDate">To Date</label>
                <input type="date" id="toDate" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                <?php echo render_svg_icon('filter', 'icon-sm'); ?>
                <span>Apply</span>
            </button>

            <a class="btn btn-secondary" href="report.php">
                <?php echo render_svg_icon('rotate-ccw', 'icon-sm'); ?>
                <span>Reset</span>
            </a>
        </form>

        <div class="search-box">
            <?php echo render_svg_icon('search', 'icon'); ?>
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search customer, medicine, date...">
        </div>
    </div>

    <div class="table-wrapper">
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Medicine</th>
                    <th>Manufacturer</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Sale Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0) : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><span class="id-pill">#<?php echo $row['sale_id']; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong>
                                <div style="font-size:0.75rem;color:#858BA3;">ID: <?php echo $row['customer_id']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['medicine_name']); ?></strong></td>
                            <td><span class="mfg-badge"><?php echo htmlspecialchars($row['manufacturer']); ?></span></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td class="amount-cell">₹<?php echo number_format((float)$row['amount'], 2); ?></td>
                            <td><?php echo $row['sale_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="empty-state" id="emptyState" style="display: <?php echo count($rows) === 0 ? 'block' : 'none'; ?>;">
            <div class="empty-state-icon"><?php echo render_svg_icon('receipt', 'icon-lg'); ?></div>
            <strong>No sales records found</strong>
            <p>Try adjusting your customer filter, date range, or search query.</p>
        </div>
    </div>
</section>

<script>
function filterTable() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const tbodyRows = document.querySelectorAll("#reportTable tbody tr");
    let visibleCount = 0;

    tbodyRows.forEach((row) => {
        const match = row.textContent.toLowerCase().includes(query);
        row.style.display = match ? "" : "none";
        if (match) visibleCount++;
    });

    const emptyState = document.getElementById("emptyState");
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? "block" : "none";
    }
}
</script>

<?php render_app_shell_end(); ?>
