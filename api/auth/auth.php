<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/management_db.php';

function management_login_path(): string {
    return '../auth/login.html';
}

function current_user(): ?array {
    global $conn;

    $userId = 0;
    if (!empty($_SESSION['user']['user_id'])) {
        $userId = (int) $_SESSION['user']['user_id'];
    } elseif (!empty($_SESSION['user_id'])) {
        // Fallback support for older sessions created before this module was integrated.
        $userId = (int) $_SESSION['user_id'];
    }

    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT user_id, full_name, email, role, phone_number, account_status FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    return $user ?: null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: ' . management_login_path());
        exit;
    }
    if (!in_array($user['account_status'], ['active', 'suspended'], true)) {
        session_destroy();
        header('Location: ' . management_login_path() . '?error=' . urlencode('Account is not active'));
        exit;
    }
    return $user;
}

function require_role(array $roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied. This page is only for: ' . htmlspecialchars(implode(', ', $roles), ENT_QUOTES, 'UTF-8'));
    }
    return $user;
}

function redirect_by_role(array $user): void {
    if ($user['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($user['role'] === 'facility_manager') {
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
