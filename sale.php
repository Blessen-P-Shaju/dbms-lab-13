<?php
include "db.php";

$message = "";

if (isset($_POST['submit'])) {

    $customer_id = $_POST['customer_id'];
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $sale_date = $_POST['sale_date'];

    $sql = "INSERT INTO sale
            (customer_id, medicine_id, quantity, sale_date)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiis",
        $customer_id,
        $medicine_id,
        $quantity,
        $sale_date
    );

    if ($stmt->execute()) {
        $message = "Record saved successfully!";
    } else {
        $message = "Error: Record could not be saved.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy Sales</title>
</head>
<body>

    <h2>Pharmacy Sales System</h2>
    <h3>Sale Entry Form</h3>

    <p><a href="report.php">View Sales Report</a></p>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="post">

        Customer:
        <select name="customer_id" required>
            <option value="">Select Customer</option>

            <?php
            $result = $conn->query("SELECT * FROM customer");

            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['customer_id'] . "'>"
                    . htmlspecialchars($row['name']) .
                    "</option>";
            }
            ?>
        </select>

        <br><br>

        Medicine:
        <select name="medicine_id" required>
            <option value="">Select Medicine</option>

            <?php
            $result = $conn->query("SELECT * FROM medicine");

            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['medicine_id'] . "'>"
                    . htmlspecialchars($row['medicine_name']) .
                    "</option>";
            }
            ?>
        </select>

        <br><br>

        Quantity:
        <input type="number" name="quantity" min="1" required>

        <br><br>

        Sale Date:
        <input type="date" name="sale_date" required>

        <br><br>

        <input type="submit" name="submit" value="Submit">

    </form>

</body>
</html>