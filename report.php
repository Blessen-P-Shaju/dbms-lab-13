<?php
include "db.php";

$sql = "SELECT c.name AS customer_name,
               m.medicine_name,
               s.quantity,
               (m.unit_price * s.quantity) AS amount,
               s.sale_date
        FROM sale s
        INNER JOIN customer c ON s.customer_id = c.customer_id
        INNER JOIN medicine m ON s.medicine_id = m.medicine_id
        ORDER BY s.sale_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Report</title>
</head>
<body>

    <h2>Pharmacy Sales Report</h2>

    <p><a href="sale.php">Record New Sale</a></p>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Customer Name</th>
            <th>Medicine Name</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Sale Date</th>
        </tr>

        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "<td>" . $row['amount'] . "</td>";
                echo "<td>" . $row['sale_date'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No records found</td></tr>";
        }
        ?>
    </table>

</body>
</html>