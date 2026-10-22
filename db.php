<?php
// Database connection for the Pharmacy Sales System.
$host = "localhost";
$username = "root";
$password = "";
$database = "pharmacy_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed. Please check db.php and make sure MySQL is running.");
}

$conn->set_charset("utf8mb4");
?>