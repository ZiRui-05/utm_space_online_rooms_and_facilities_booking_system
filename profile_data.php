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
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, utm_id, ic_no, phone_number, department FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $bookingStmt = $pdo->prepare(
        'SELECT b.booking_id, b.resource_type, b.booking_start, b.booking_end, b.booking_status, b.created_at,
                r.room_name, f.facility_name
         FROM bookings b
         LEFT JOIN rooms r ON b.room_id = r.room_id
         LEFT JOIN facilities f ON b.facility_id = f.facility_id
         WHERE b.user_id = :user_id
         ORDER BY b.booking_start DESC, b.created_at DESC'
    );
    $bookingStmt->execute(['user_id' => $userId]);
    $bookings = $bookingStmt->fetchAll();

    echo json_encode(['success' => true, 'user' => $user, 'bookings' => $bookings]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $phoneNumber = trim($input['phone_number'] ?? '');
    $department = trim($input['department'] ?? '');

    $stmt = $pdo->prepare('UPDATE users SET phone_number = :phone_number, department = :department WHERE user_id = :user_id');
    $stmt->execute([
        'phone_number' => $phoneNumber === '' ? null : $phoneNumber,
        'department' => $department === '' ? null : $department,
        'user_id' => $userId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
