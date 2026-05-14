<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$loginId = trim($input['login_id'] ?? ($input['email'] ?? ''));
$password = $input['password'] ?? '';

if ($loginId === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email/UTM ID and password are required']);
    exit;
}

require __DIR__ . '/../../config/db.php';

$loginField = str_contains($loginId, '@') ? 'email' : 'utm_id';
$stmt = $pdo->prepare("SELECT user_id, full_name, email, utm_id, password_hash, role, account_status FROM users WHERE {$loginField} = :login_id LIMIT 1");
$stmt->execute(['login_id' => $loginId]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email/UTM ID or password']);
    exit;
}

if ($user['account_status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Account is not active']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'user_id' => (int)$user['user_id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'utm_id' => $user['utm_id'],
    'role' => $user['role'],
];

echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['user']]);
