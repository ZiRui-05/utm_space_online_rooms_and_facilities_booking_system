<?php
require_once __DIR__ . '/../config/app.php';

// Management module database bridge.
// Uses the same database environment settings as config/db.php,
// while keeping mysqli support for the imported management pages.
$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: '3306');
$dbname = getenv('DB_NAME') ?: 'utm_space_booking_system';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    $conn->set_charset('utf8mb4');
    require_once __DIR__ . '/booking_expiry.php';
    expire_stale_bookings_mysqli($conn);
} catch (mysqli_sql_exception $e) {
    die('Database connection failed. Please check your database settings. Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
