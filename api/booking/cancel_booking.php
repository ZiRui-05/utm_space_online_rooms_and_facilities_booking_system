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
require_once __DIR__ . '/../../includes/booking_constraints.php';

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

    $lockContext = booking_lock_context_pdo($pdo, $bookingId);
    if (!$lockContext || (int)$lockContext['user_id'] !== $userId) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found.'
        ]);
        exit;
    }
    $stmt = $pdo->prepare(
        "SELECT booking_id, booking_status, booking_start, total_price, payment_status
         FROM bookings
         WHERE booking_id = ? AND user_id = ?
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

    if (!in_array((string)$booking['booking_status'], ['pending', 'approved'], true)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Only pending or approved bookings can be cancelled.'
        ]);
        exit;
    }

    if (strtotime((string)$booking['booking_start']) <= time()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Bookings cannot be cancelled after the booking start time.'
        ]);
        exit;
    }

    $update = $pdo->prepare(
        "UPDATE bookings
         SET booking_status = 'cancelled',
             request_fingerprint = NULL,
             payment_status = CASE
                 WHEN total_price > 0 AND payment_status IN ('paid', 'pending_verification') THEN 'refunded'
                 ELSE payment_status
             END
         WHERE booking_id = ?
           AND user_id = ?
           AND booking_status IN ('pending', 'approved')
           AND booking_start > NOW()"
    );
    $update->execute([$bookingId, $userId]);

    if ($update->rowCount() < 1) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Booking could not be cancelled. Please refresh and try again.'
        ]);
        exit;
    }

    booking_release_claims_pdo($pdo, $bookingId);
    $pdo->commit();

    create_user_notification_pdo($pdo, $userId, $bookingId, 'Booking cancelled', 'Your booking request #' . $bookingId . ' has been cancelled.', 'booking_status');

    echo json_encode([
        'success' => true,
        'message' => 'Booking has been cancelled successfully.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (booking_is_retryable_database_error($e)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'code' => 'concurrent_update',
            'message' => 'Another booking update is in progress. Please try again.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to cancel booking.'
        ]);
    }
}
