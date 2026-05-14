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

$requestId = bin2hex(random_bytes(8));

require __DIR__ . '/../../config/db.php';
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

$logFile = __DIR__ . '/password_reset_mail.log';
$logBase = sprintf('[%s] request_id=%s email_hash=%s ', date('c'), $requestId, hash('sha256', strtolower($email)));

if (!$user) {
    file_put_contents($logFile, $logBase . "user_exists=no send_attempted=no\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'message' => 'If this email exists, a reset link has been sent.',
        'request_id' => $requestId,
    ]);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$resetLink = sprintf('%s://%s/pages/auth/reset-password.html?email=%s', $scheme, $host, urlencode($email));

$subject = 'UNIRESERVE Password Reset';
$message = "Hello,\n\nPlease use the following link to reset your password:\n{$resetLink}\n\nIf you did not request this, please ignore this email.";
$headers = [
    'From: UNIRESERVE <no-reply@unireserve.local>',
    'Reply-To: no-reply@unireserve.local',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

$mailSent = mail($email, $subject, $message, implode("\r\n", $headers));
file_put_contents($logFile, $logBase . sprintf("user_exists=yes send_attempted=yes mail_return=%s target=%s\n", $mailSent ? 'true' : 'false', $email), FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'If this email exists, a reset link has been sent.',
    'request_id' => $requestId,
]);
