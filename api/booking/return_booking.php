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
require_once __DIR__ . '/../../includes/notifications.php';

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
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.booking_status,
                CASE WHEN b.booking_start <= NOW() THEN 1 ELSE 0 END AS has_started,
                COALESCE(r.room_name, f.facility_name, 'Resource') AS resource_name
         FROM bookings b
         LEFT JOIN rooms r ON r.room_id = b.room_id
         LEFT JOIN facilities f ON f.facility_id = b.facility_id
         WHERE b.booking_id = ? AND b.user_id = ?
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found.'
        ]);
        exit;
    }

    if ((string)$booking['booking_status'] !== 'approved') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Only approved bookings can be returned.'
        ]);
        exit;
    }

    if ((int)$booking['has_started'] !== 1) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'This booking cannot be returned before its start time.'
        ]);
        exit;
    }

    $update = $pdo->prepare(
        "UPDATE bookings
         SET booking_status = 'completed'
         WHERE booking_id = ?
           AND user_id = ?
           AND booking_status = 'approved'
           AND booking_start <= NOW()"
    );
    $update->execute([$bookingId, $userId]);

    if ($update->rowCount() < 1) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Booking could not be returned. Please refresh and try again.'
        ]);
        exit;
    }

    $pdo->commit();

    try {
        create_user_notification_pdo(
            $pdo,
            $userId,
            $bookingId,
            'Booking return confirmed',
            'Your return for booking #' . $bookingId . ' (' . (string)$booking['resource_name'] . ') has been recorded.',
            'booking_status'
        );
    } catch (Throwable $notificationError) {
        error_log('Failed to create return notification for booking #' . $bookingId . ': ' . $notificationError->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Booking has been returned successfully.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to return booking.'
    ]);
}
