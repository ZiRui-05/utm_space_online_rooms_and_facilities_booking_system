<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/db.php';

$userId = (int)$_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, utm_id, phone_number FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fullName = trim($input['full_name'] ?? '');
    $phoneNumber = trim($input['phone_number'] ?? '');

    if ($fullName === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Full Name is required']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone_number = :phone_number WHERE user_id = :user_id');
    $stmt->execute([
        'full_name' => $fullName,
        'phone_number' => $phoneNumber === '' ? null : $phoneNumber,
        'user_id' => $userId,
    ]);

    $_SESSION['user']['full_name'] = $fullName;

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);