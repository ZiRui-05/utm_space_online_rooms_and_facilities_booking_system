<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$email = '';

if (is_array($input)) {
    $email = trim((string)($input['email'] ?? ''));
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

require __DIR__ . '/db.php';
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => true, 'message' => 'If this email exists, a reset link has been sent.']);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$resetLink = sprintf('%s://%s/reset-password.html?email=%s', $scheme, $host, urlencode($email));

$subject = 'UNIRESERVE Password Reset';
$message = "Hello,\n\nPlease use the following link to reset your password:\n{$resetLink}\n\nIf you did not request this, please ignore this email.";
$headers = "From: no-reply@unireserve.local\r\n";