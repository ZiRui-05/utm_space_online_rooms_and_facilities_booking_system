<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit;
}

$userId = (int)($_SESSION['user']['user_id'] ?? 0);
if ($userId <= 0) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit;
}

require __DIR__ . '/../../config/db.php';

$stmt = $pdo->prepare('SELECT user_id, full_name, email, utm_id, role, account_status FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !in_array($user['account_status'], ['active', 'suspended'], true)) {
    session_unset();
    session_destroy();
    http_response_code(403);
    echo json_encode([
        'authenticated' => false,
        'message' => 'Account is not active',
    ]);
    exit;
}

$_SESSION['user'] = [
    'user_id' => (int)$user['user_id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'utm_id' => $user['utm_id'],
    'role' => $user['role'],
    'account_status' => $user['account_status'],
];

echo json_encode(['authenticated' => true, 'user' => $_SESSION['user']]);
