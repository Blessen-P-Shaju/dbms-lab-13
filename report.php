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

<div class="report-hero">
    <div>
        <span class="editorial-kicker">
            <?php echo render_svg_icon('bar-chart', 'icon-sm'); ?>
            <span>Revenue &amp; Dispensing Analytics</span>
        </span>
        <h1 class="editorial-title">Sales <span class="accent-word">Performance</span> Overview.</h1>
        <p class="editorial-desc">Real-time transaction metrics, medicine revenue breakdown, and filterable sales history.</p>
    </div>

    <div class="report-hero-revenue">
        <span>Total Matching Revenue</span>
        <strong data-countup="<?php echo number_format($total_revenue, 2, '.', ''); ?>" data-prefix="₹" data-decimals="2">₹<?php echo number_format($total_revenue, 2); ?></strong>
    </div>
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
        <div>
            <h3>Revenue by Medicine</h3>
            <span>Hover for details · Click a bar to filter the table</span>
        </div>
        <button type="button" class="pill-btn" id="clearChartFilter" hidden>Clear Medicine Filter</button>
    </div>
    <div class="chart-bars">
        <?php foreach (array_slice($medicine_totals, 0, 6, true) as $medName => $medRev) :
            $pct = $max_med_revenue > 0 ? round(($medRev / $max_med_revenue) * 100, 1) : 0;
            $share = $total_revenue > 0 ? round(($medRev / $total_revenue) * 100, 1) : 0;
        ?>
            <div class="chart-bar-row is-clickable"
                 role="button"
                 tabindex="0"
                 data-medicine="<?php echo htmlspecialchars($medName); ?>"
                 data-revenue="<?php echo number_format($medRev, 2); ?>"
                 data-share="<?php echo $share; ?>">
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
    <div class="chart-tooltip-bar" id="chartTooltipBar">Click any medicine bar above to filter matching sales records.</div>
</section>
<?php endif; ?>

<!-- Filters & Sales Table -->
<section class="report-card">
    <div class="quick-date-pills" aria-label="Quick Date Filters">
        <span class="control-label">Quick Range</span>
        <button type="button" class="pill-btn" data-range="today">Today</button>
        <button type="button" class="pill-btn" data-range="7">Last 7 Days</button>
        <button type="button" class="pill-btn" data-range="30">Last 30 Days</button>
    </div>

    <div class="filter-toolbar">
        <form method="get" action="" class="filter-form" id="reportFilterForm">
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
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search customer, medicine... (Press /)">
            <button type="button" class="search-clear-btn" id="clearSearchBtn" aria-label="Clear search">&times;</button>
        </div>
    </div>

    <div class="table-meta-bar">
        <span class="record-count-pill" id="visibleCountPill">
            Showing <strong id="visibleCount"><?php echo count($rows); ?></strong> of <?php echo count($rows); ?> records
        </span>
        <button type="button" class="btn btn-secondary btn-sm" id="exportCsvBtn">
            <span>Export Visible CSV</span>
        </button>
    </div>

    <div class="table-wrapper">
        <table id="reportTable">
            <thead>
                <tr>
                    <th class="sortable" data-col="0" data-type="num">Sale ID <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="1" data-type="text">Customer <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="2" data-type="text">Address <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="3" data-type="text">Phone <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="4" data-type="text">Medicine <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="5" data-type="text">Manufacturer <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="6" data-type="num">Qty <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="7" data-type="num">Amount <span class="sort-indicator">↕</span></th>
                    <th class="sortable" data-col="8" data-type="date">Sale Date <span class="sort-indicator">↕</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0) : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr data-medicine="<?php echo htmlspecialchars($row['medicine_name']); ?>">
                            <td data-sort-val="<?php echo $row['sale_id']; ?>"><span class="id-pill">#<?php echo $row['sale_id']; ?></span></td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['customer_name']); ?>">
                                <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong>
                                <div style="font-size:0.75rem;color:#858BA3;">ID: <?php echo $row['customer_id']; ?></div>
                            </td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['address']); ?>"><?php echo htmlspecialchars($row['address']); ?></td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['phone']); ?>"><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['medicine_name']); ?>"><strong><?php echo htmlspecialchars($row['medicine_name']); ?></strong></td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['manufacturer']); ?>"><span class="mfg-badge"><?php echo htmlspecialchars($row['manufacturer']); ?></span></td>
                            <td data-sort-val="<?php echo $row['quantity']; ?>"><?php echo $row['quantity']; ?></td>
                            <td data-sort-val="<?php echo (float)$row['amount']; ?>" class="amount-cell">₹<?php echo number_format((float)$row['amount'], 2); ?></td>
                            <td data-sort-val="<?php echo $row['sale_date']; ?>"><?php echo $row['sale_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="empty-state" id="emptyState" style="display: <?php echo count($rows) === 0 ? 'block' : 'none'; ?>;">
            <svg width="88" height="72" viewBox="0 0 88 72" fill="none" aria-hidden="true" style="margin-bottom:10px;">
                <circle cx="44" cy="36" r="32" fill="#EEF2FF"/>
                <g transform="translate(32, 16) rotate(24 12 20)">
                    <rect x="0" y="0" width="20" height="42" rx="10" fill="#3B5BFF"/>
                    <path d="M0 21 H20 V32 A10 10 0 0 1 0 32 Z" fill="#FFFFFF"/>
                </g>
            </svg>
            <strong>No sales records found</strong>
            <p>Try adjusting your customer filter, date range, or search query.</p>
        </div>
    </div>
</section>

<script>
let selectedMedicineFilter = "";

function filterTable() {
    const searchInput = document.getElementById("searchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");
    const query = searchInput.value.toLowerCase();
    if (clearSearchBtn) {
        clearSearchBtn.classList.toggle("visible", query.length > 0);
    }

    const tbodyRows = document.querySelectorAll("#reportTable tbody tr");
    let visibleCount = 0;

    tbodyRows.forEach((row) => {
        const textMatch = row.textContent.toLowerCase().includes(query);
        const medMatch = !selectedMedicineFilter || row.dataset.medicine === selectedMedicineFilter;
        const show = textMatch && medMatch;
        row.style.display = show ? "" : "none";
        if (show) visibleCount++;
    });

    const visibleCountEl = document.getElementById("visibleCount");
    if (visibleCountEl) visibleCountEl.textContent = String(visibleCount);

    const emptyState = document.getElementById("emptyState");
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? "block" : "none";
    }
}

(function () {
    const searchInput      = document.getElementById("searchInput");
    const clearSearchBtn   = document.getElementById("clearSearchBtn");
    const clearChartBtn    = document.getElementById("clearChartFilter");
    const chartTooltipBar  = document.getElementById("chartTooltipBar");
    const chartRows        = document.querySelectorAll(".chart-bar-row[data-medicine]");
    const quickDateBtns    = document.querySelectorAll(".quick-date-pills [data-range]");
    const filterForm       = document.getElementById("reportFilterForm");
    const fromDateInput    = document.getElementById("fromDate");
    const toDateInput      = document.getElementById("toDate");
    const exportCsvBtn     = document.getElementById("exportCsvBtn");
    const sortableHeaders  = document.querySelectorAll("#reportTable th.sortable");

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener("click", function () {
            searchInput.value = "";
            const topMirror = document.getElementById("topbarSearchMirror");
            if (topMirror) topMirror.value = "";
            filterTable();
            searchInput.focus();
        });
    }

    // Interactive Chart Hover + Click Filter
    function setChartFilter(medName) {
        selectedMedicineFilter = medName;
        chartRows.forEach((r) => r.classList.toggle("is-selected", r.dataset.medicine === medName));
        if (clearChartBtn) clearChartBtn.hidden = !medName;
        if (chartTooltipBar) {
            chartTooltipBar.textContent = medName
                ? "Filtering table by: " + medName
                : "Click any medicine bar above to filter matching sales records.";
        }
        filterTable();
    }

    chartRows.forEach((row) => {
        row.addEventListener("mouseenter", function () {
            if (chartTooltipBar) {
                chartTooltipBar.textContent = this.dataset.medicine + " — Revenue: ₹" + this.dataset.revenue + " (" + this.dataset.share + "% of total)";
            }
        });
        row.addEventListener("click", function () {
            const clicked = this.dataset.medicine;
            setChartFilter(selectedMedicineFilter === clicked ? "" : clicked);
        });
        row.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                this.click();
            }
        });
    });

    if (clearChartBtn) {
        clearChartBtn.addEventListener("click", () => setChartFilter(""));
    }

    // Quick Date Filters (working with existing PHP GET filters)
    function formatYMD(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return y + "-" + m + "-" + day;
    }

    quickDateBtns.forEach((btn) => {
        btn.addEventListener("click", function () {
            const range = this.dataset.range;
            const today = new Date();
            const from = new Date(today);
            if (range === "7") from.setDate(today.getDate() - 6);
            if (range === "30") from.setDate(today.getDate() - 29);
            fromDateInput.value = formatYMD(from);
            toDateInput.value = formatYMD(today);
            filterForm.submit();
        });
    });

    // Clickable Column Sorting
    let currentSortCol = -1;
    let currentSortDir = "asc";

    sortableHeaders.forEach((th) => {
        th.addEventListener("click", function () {
            const colIdx = parseInt(this.dataset.col, 10);
            const type = this.dataset.type || "text";
            currentSortDir = (currentSortCol === colIdx && currentSortDir === "asc") ? "desc" : "asc";
            currentSortCol = colIdx;

            sortableHeaders.forEach((h) => {
                h.classList.remove("sort-asc", "sort-desc");
                h.querySelector(".sort-indicator").textContent = "↕";
            });
            this.classList.add(currentSortDir === "asc" ? "sort-asc" : "sort-desc");
            this.querySelector(".sort-indicator").textContent = currentSortDir === "asc" ? "▲" : "▼";

            const tbody = document.querySelector("#reportTable tbody");
            const rowsArr = Array.from(tbody.querySelectorAll("tr"));
            rowsArr.sort((a, b) => {
                const av = a.children[colIdx].dataset.sortVal || a.children[colIdx].textContent.trim();
                const bv = b.children[colIdx].dataset.sortVal || b.children[colIdx].textContent.trim();
                let cmp = 0;
                if (type === "num") {
                    cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
                } else {
                    cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: "base" });
                }
                return currentSortDir === "asc" ? cmp : -cmp;
            });
            rowsArr.forEach((r) => tbody.appendChild(r));
        });
    });

    // Safe CSV Export (Visible Filtered Rows Only + Formula Injection Protection)
    function csvEscapeSafe(val) {
        let str = String(val == null ? "" : val).replace(/\r?\n|\r/g, " ").trim();
        if (/^[=+\-@\t\r]/.test(str)) {
            str = "'" + str;
        }
        if (/[",]/.test(str)) {
            str = '"' + str.replace(/"/g, '""') + '"';
        }
        return str;
    }

    if (exportCsvBtn) {
        exportCsvBtn.addEventListener("click", function () {
            const headers = ["Sale ID", "Customer", "Address", "Phone", "Medicine", "Manufacturer", "Quantity", "Amount", "Sale Date"];
            const visibleRows = Array.from(document.querySelectorAll("#reportTable tbody tr"))
                .filter((r) => r.style.display !== "none");

            if (visibleRows.length === 0) {
                if (typeof window.showToast === "function") {
                    window.showToast("Nothing to Export", "No visible sales records match your current filter.", "error");
                }
                return;
            }

            const lines = [headers.map(csvEscapeSafe).join(",")];
            visibleRows.forEach((row) => {
                const cells = Array.from(row.children).map((td) => td.dataset.sortVal !== undefined ? td.dataset.sortVal : td.textContent);
                lines.push(cells.map(csvEscapeSafe).join(","));
            });

            const blob = new Blob([lines.join("\r\n")], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "pharmacare-sales-report.csv";
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);

            if (typeof window.showToast === "function") {
                window.showToast("CSV Exported", "Exported " + visibleRows.length + " visible sales record(s).", "success");
            }
        });
    }
})();
</script>

<?php render_app_shell_end(); ?>
