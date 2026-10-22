<?php
require_once "db.php";

$sql = "SELECT c.name AS customer_name,
               m.medicine_name,
               s.quantity,
               (m.unit_price * s.quantity) AS amount,
               s.sale_date
        FROM `sale` AS s
        INNER JOIN customer AS c ON s.customer_id = c.customer_id
        INNER JOIN medicine AS m ON s.medicine_id = m.medicine_id
        ORDER BY s.sale_date DESC, s.sale_id DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Could not load the sales report. Please check the database tables.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Sales | Report</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f6f8; color: #1d2935; }
        header { background: #123b35; color: white; padding: 24px 7%; }
        header h1 { margin: 0 0 6px; font-size: 27px; }
        header p { margin: 0; color: #d8e8e4; }
        main { width: min(1100px, 94%); margin: 34px auto; }
        .panel { background: white; padding: 26px; border-radius: 14px; box-shadow: 0 8px 24px rgba(20,40,50,.08); }
        .topline { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        h2 { margin: 0 0 8px; }
        .button { display: inline-block; background: #176b57; color: white; text-decoration: none; padding: 11px 16px; border-radius: 8px; }
        .table-wrap { overflow-x: auto; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #e2e8ec; }
        th { background: #edf4f2; color: #17463d; }
        tbody tr:hover { background: #f8fbfa; }
        .empty { padding: 22px; background: #f5f8f9; border-radius: 8px; margin-top: 20px; }
        footer { text-align: center; color: #61717e; padding: 15px; font-size: 13px; }
    </style>
</head>
<body>
<header>
    <h1>Pharmacy Sales System</h1>
    <p>Sales Report · Roll Number 13 · Q13</p>
</header>
<main>
    <section class="panel">
        <div class="topline">
            <div>
                <h2>Medicine Sales Report</h2>
                <p>Customer, medicine, quantity, amount and sale date.</p>
            </div>
            <a class="button" href="sale.php">+ Record New Sale</a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Sale Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row["customer_name"], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row["medicine_name"], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$row["quantity"] ?></td>
                            <td>₹<?= number_format((float)$row["amount"], 2) ?></td>
                            <td><?= htmlspecialchars($row["sale_date"], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">No sales have been recorded yet. Use “Record New Sale” to add the first sale.</div>
        <?php endif; ?>
    </section>
</main>
<footer>Pharmacy Sales System · PHP and MySQL</footer>
</body>
</html>