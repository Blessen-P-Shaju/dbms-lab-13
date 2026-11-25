<?php
// Load .env file if present (for local/custom setups)
$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (getenv($name) === false) {
            putenv("$name=$value");
        }
    }
}

// Database connection for the Pharmacy Sales System.
$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$database = getenv('DB_NAME') ?: 'pharmacy_db';
$port     = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306;

if (getenv('DB_SSL') === 'true') {
    $conn = mysqli_init();
    $conn->ssl_set(null, null, null, null, null);
    $conn->real_connect($host, $username, $password, $database, $port, null, MYSQLI_CLIENT_SSL);
} else {
    $conn = new mysqli($host, $username, $password, $database, $port);
}

if ($conn->connect_error) {
    die("Database connection failed. Please check db.php and make sure MySQL is running.");
}

$conn->set_charset("utf8mb4");
?>
