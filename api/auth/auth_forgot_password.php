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
$stmt = $pdo->prepare('SELECT user_id, full_name FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

$logFile = __DIR__ . '/password_reset_mail.log';
$logBase = sprintf('[%s] request_id=%s email_hash=%s ', date('c'), $requestId, hash('sha256', strtolower($email)));

if (!$user) {
    file_put_contents($logFile, $logBase . "user_exists=no send_attempted=no\n", FILE_APPEND);
    echo json_encode(['success' => true, 'message' => 'If this email exists, a reset code has been sent.', 'request_id' => $requestId]);
    exit;
}

$otpCode = (string)random_int(100000, 999999);
$otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
$expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);

$clearStmt = $pdo->prepare('DELETE FROM password_reset_otps WHERE user_id = :user_id');
$clearStmt->execute(['user_id' => $user['user_id']]);

$insertStmt = $pdo->prepare('INSERT INTO password_reset_otps (user_id, otp_hash, expires_at, attempts) VALUES (:user_id, :otp_hash, :expires_at, 0)');
$insertStmt->execute([
    'user_id' => $user['user_id'],
    'otp_hash' => $otpHash,
    'expires_at' => $expiresAt,
]);

require_once __DIR__ . '/../../config/mailer.php';

$subject = 'SPACEBOOK Password Reset Code';
$message = "Hello,\n\nYour password reset code is: {$otpCode}\n\nThis code expires in 15 minutes.\nIf you did not request this, please ignore this email.";
$htmlMessage = '<p>Hello,</p><p>Your password reset code is:</p><p style="font-size:24px;font-weight:bold;letter-spacing:3px;">' . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') . '</p><p>This code expires in 15 minutes.</p><p>If you did not request this, please ignore this email.</p>';
$sendResult = sendMail($email, (string)($user['full_name'] ?? ''), $subject, $message, $htmlMessage);

file_put_contents($logFile, $logBase . sprintf("user_exists=yes send_attempted=yes mail_return=%s target=%s detail=%s\n", $sendResult['success'] ? 'true' : 'false', $email, $sendResult['message']), FILE_APPEND);

echo json_encode(['success' => true, 'message' => 'If this email exists, a reset code has been sent.', 'request_id' => $requestId]);
