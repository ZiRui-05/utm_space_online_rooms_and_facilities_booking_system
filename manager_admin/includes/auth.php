<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: '3306');
$dbName = getenv('DB_NAME') ?: 'utm_space_booking_system';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName, $port);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}
$conn->set_charset('utf8mb4');
require_once __DIR__ . '/../../includes/booking_expiry.php';
expire_stale_bookings_mysqli($conn);

function require_role(array $allowedRoles): array
{
    global $conn;

    $userId = $_SESSION['user']['user_id'] ?? null;
    if (!$userId) {
        header('Location: ../pages/auth/login.php');
        exit;
    }

    $stmt = $conn->prepare('SELECT user_id, full_name, email, role, account_status FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || ($user['account_status'] ?? 'inactive') !== 'active') {
        session_unset();
        session_destroy();
        header('Location: ../pages/auth/login.php?error=' . urlencode('Session expired. Please login again.'));
        exit;
    }

    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('<h1>403 Forbidden</h1><p>You are not allowed to access this page.</p>');
    }

    return $user;
}
