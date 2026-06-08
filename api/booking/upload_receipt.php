<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$bookingId = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user']['user_id'];

if ($bookingId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit;
}

if (!isset($_FILES['receipt']) || !is_uploaded_file($_FILES['receipt']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Receipt file required']);
    exit;
}

$receipt = $_FILES['receipt'];
if (($receipt['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Receipt upload failed']);
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if (($receipt['size'] ?? 0) <= 0 || $receipt['size'] > $maxBytes) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Receipt file must be 5MB or smaller']);
    exit;
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $fileInfo->file($receipt['tmp_name']) ?: '';
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Receipt must be JPG, PNG, WEBP, or PDF']);
    exit;
}

$rawReceipt = file_get_contents($receipt['tmp_name']);
if ($rawReceipt === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Unable to read receipt file']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT booking_id, total_price, booking_status, payment_status
         FROM bookings
         WHERE booking_id = ? AND user_id = ?
         LIMIT 1"
         . " FOR UPDATE"
    );
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    if ((float)$booking['total_price'] <= 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment is unavailable for free bookings']);
        exit;
    }

    if (!in_array((string)$booking['booking_status'], ['pending', 'approved'], true)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment receipt can only be uploaded for pending or approved bookings']);
        exit;
    }

    if ($booking['payment_status'] === 'paid') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This booking has already been paid']);
        exit;
    }

    $update = $pdo->prepare(
        "UPDATE bookings
         SET payment_proof_base64 = ?,
             payment_proof_mime = ?,
             payment_status = 'pending_verification'
         WHERE booking_id = ? AND user_id = ?
           AND booking_status IN ('pending', 'approved')"
    );
    $update->execute([base64_encode($rawReceipt), $mime, $bookingId, $userId]);

    if ($update->rowCount() < 1) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment receipt could not be uploaded. Please refresh and try again.']);
        exit;
    }

    $pdo->commit();

    create_user_notification_pdo($pdo, $userId, $bookingId, 'Payment receipt uploaded', 'Your payment receipt for booking #' . $bookingId . ' was uploaded and is pending verification.', 'payment');

    echo json_encode([
        'success' => true,
        'message' => 'Receipt uploaded. Payment is pending verification.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload receipt']);
}
