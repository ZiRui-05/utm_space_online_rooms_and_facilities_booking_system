<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fullName = trim($input['full_name'] ?? '');
$utmId = trim($input['utm_id'] ?? '');
$icNo = trim($input['ic_no'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($fullName === '' || $utmId === '' || $icNo === '' || $email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Full name, UTM ID, IC number, email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$emailDomain = strtolower(substr(strrchr($email, '@') ?: '', 1));
$staffDomains = ['utm.my', 'utm.edu.my', 'utmspace.edu.my', 'utmspace.my'];

if ($emailDomain === 'graduate.utm.my') {
    $role = 'student';
} elseif (in_array($emailDomain, $staffDomains, true)) {
    $role = 'staff';
} else {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Use @graduate.utm.my for student or UTM/UTMSpace staff domains.'
    ]);
    exit;
}


if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

require __DIR__ . '/../../config/db.php';

$checkStmt = $pdo->prepare('SELECT user_id, email, utm_id, ic_no FROM users WHERE email = :email OR utm_id = :utm_id OR ic_no = :ic_no LIMIT 1');
$checkStmt->execute([
    'email' => $email,
    'utm_id' => $utmId,
    'ic_no' => $icNo,
]);
$existingUser = $checkStmt->fetch();
if ($existingUser) {
    http_response_code(409);
    if ($existingUser['email'] === $email) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
    } elseif ($existingUser['utm_id'] === $utmId) {
        echo json_encode(['success' => false, 'message' => 'UTM ID already registered']);
    } else {
        echo json_encode(['success' => false, 'message' => 'IC number already registered']);
    }
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insertStmt = $pdo->prepare('INSERT INTO users (full_name, utm_id, ic_no, email, password_hash, role, account_status) VALUES (:full_name, :utm_id, :ic_no, :email, :password_hash, :role, :account_status)');
$insertStmt->execute([
    'full_name' => $fullName,
    'utm_id' => $utmId,
    'ic_no' => $icNo,
    'email' => $email,
    'password_hash' => $passwordHash,
    'role' => $role,
    'account_status' => 'active'
]);

echo json_encode(['success' => true, 'message' => 'Registration successful']);
