<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/management_db.php';

function current_management_user(): ?array {
    global $conn;

    $userId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT user_id, full_name, email, role, phone_number, account_status FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    return $user ?: null;
}

function require_management_login(): array {
    $user = current_management_user();
    if (!$user) {
        header('Location: ../auth/login.html');
        exit;
    }

    if (!in_array(($user['account_status'] ?? ''), ['active', 'suspended'], true)) {
        session_destroy();
        header('Location: ../auth/login.html?error=Account%20is%20not%20active');
        exit;
    }

    return $user;
}

function require_management_role(array $roles): array {
    $user = require_management_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied. This page is only for: ' . htmlspecialchars(implode(', ', $roles), ENT_QUOTES, 'UTF-8'));
    }
    return $user;
}

function redirect_management_by_role(array $user): void {
    if (($user['role'] ?? '') === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif (($user['role'] ?? '') === 'facility_manager') {
        header('Location: facility_manager_dashboard.php');
    } else {
        header('Location: ../../homepage.php');
    }
    exit;
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
