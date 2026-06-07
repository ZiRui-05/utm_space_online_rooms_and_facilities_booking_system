<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function is_strong_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
$otp = trim((string)($input['otp'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_strong_password($password) || !preg_match('/^\d{6}$/', $otp)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email, 6-digit OTP and strong password are required']);
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Invalid reset request']);
    exit;
}

$otpStmt = $pdo->prepare('SELECT id, otp_hash, expires_at, attempts FROM password_reset_otps WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
$otpStmt->execute(['user_id' => $user['user_id']]);
$otpRow = $otpStmt->fetch();

if (!$otpRow) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No OTP request found']);
    exit;
}

if (strtotime((string)$otpRow['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'OTP expired']);
    exit;
}

if ((int)$otpRow['attempts'] >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Request a new OTP']);
    exit;
}

if (!password_verify($otp, (string)$otpRow['otp_hash'])) {
    $incStmt = $pdo->prepare('UPDATE password_reset_otps SET attempts = attempts + 1 WHERE id = :id');
    $incStmt->execute(['id' => $otpRow['id']]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$updateStmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
$updateStmt->execute(['password_hash' => $passwordHash, 'user_id' => $user['user_id']]);

$deleteStmt = $pdo->prepare('DELETE FROM password_reset_otps WHERE user_id = :user_id');
$deleteStmt->execute(['user_id' => $user['user_id']]);
create_user_notification_pdo($pdo, (int)$user['user_id'], null, 'Password changed', 'Your account password was changed successfully.', 'security');

echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
