<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

require __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$bookingId = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user']['user_id'];

if ($bookingId <= 0) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Booking ID required'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET booking_status = 'cancelled'
         WHERE booking_id = ?
           AND user_id = ?
           AND booking_status IN ('pending', 'approved')"
    );
    $stmt->execute([$bookingId, $userId]);

    if ($stmt->rowCount() < 1) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Only pending or approved bookings can be cancelled.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Booking has been cancelled successfully.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to cancel booking.'
    ]);
}
