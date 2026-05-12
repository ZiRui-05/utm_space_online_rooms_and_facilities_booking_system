<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email and valid password are required']);
    exit;
}

require __DIR__ . '/db.php';
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Invalid reset request']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$updateStmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
$updateStmt->execute(['password_hash' => $passwordHash, 'user_id' => $user['user_id']]);

echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
