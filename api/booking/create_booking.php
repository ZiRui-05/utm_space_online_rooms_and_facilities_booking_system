<?php
header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$userId = (int)($_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0));
$resourceType = strtolower(trim($_POST['resource_type'] ?? 'facility'));
$resourceId = (int)($_POST['resource_id'] ?? 0);
$bookingDate = trim($_POST['booking_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');
$endTime = trim($_POST['end_time'] ?? '');
$remarks = trim($_POST['comments'] ?? '');
$purpose = trim($_POST['purpose'] ?? 'General booking request');

if (!in_array($resourceType, ['room', 'facility'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
    exit;
}

if (
    $userId <= 0 ||
    $resourceId <= 0 ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate) ||
    !preg_match('/^\d{2}:\d{2}$/', $startTime) ||
    !preg_match('/^\d{2}:\d{2}$/', $endTime)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

$bookingStart = $bookingDate . ' ' . $startTime . ':00';
$bookingEnd = $bookingDate . ' ' . $endTime . ':00';

if (strtotime($bookingEnd) <= strtotime($bookingStart)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'End time must be later than start time']);
    exit;
}

$startParts = array_map('intval', explode(':', $startTime));
$endParts = array_map('intval', explode(':', $endTime));
$startMinutes = ($startParts[0] * 60) + $startParts[1];
$endMinutes = ($endParts[0] * 60) + $endParts[1];

if (
    $startMinutes < (8 * 60) ||
    $startMinutes > (16 * 60) ||
    $endMinutes < (9 * 60) ||
    $endMinutes > (17 * 60)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking time must stay within 08:00 to 17:00']);
    exit;
}

if (($startMinutes % 15) !== 0 || ($endMinutes % 15) !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking time must use 15-minute units']);
    exit;
}

if (($endMinutes - $startMinutes) < 60) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Minimum booking duration is 1 hour']);
    exit;
}

$bookingTimezone = new DateTimeZone('Asia/Kuala_Lumpur');
$selectedBookingDate = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate, $bookingTimezone);
$today = new DateTimeImmutable('today', $bookingTimezone);
$latestBookingDate = $today->modify('+2 days');

if (
    !$selectedBookingDate ||
    $selectedBookingDate->format('Y-m-d') !== $bookingDate ||
    $selectedBookingDate < $today ||
    $selectedBookingDate > $latestBookingDate
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking date must be within 3 days including today']);
    exit;
}

$selectedBookingStart = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $bookingStart, $bookingTimezone);
$nowMalaysia = new DateTimeImmutable('now', $bookingTimezone);
if (!$selectedBookingStart || $selectedBookingStart < $nowMalaysia) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot book a time slot that starts before the current time']);
    exit;
}

try {
    $stmtRole = $pdo->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    $stmtRole->execute([$userId]);
    $role = strtolower((string)($stmtRole->fetchColumn() ?: 'guest'));
    $isFree = ($role === 'student');

    $table = $resourceType === 'room' ? 'rooms' : 'facilities';
    $idCol = $resourceType === 'room' ? 'room_id' : 'facility_id';

    $stmtPrice = $pdo->prepare("SELECT price_per_day FROM {$table} WHERE {$idCol} = ? LIMIT 1");
    $stmtPrice->execute([$resourceId]);
    $pricePerDay = (float)($stmtPrice->fetchColumn() ?? 0);

    $totalPrice = $isFree ? 0.0 : $pricePerDay;

    $roomId = $resourceType === 'room' ? $resourceId : null;
    $facilityId = $resourceType === 'facility' ? $resourceId : null;

    $sql = "INSERT INTO bookings
    (user_id, resource_type, room_id, facility_id, booking_start, booking_end, purpose, remarks, price_per_day, total_price, booking_status, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId,
        $resourceType,
        $roomId,
        $facilityId,
        $bookingStart,
        $bookingEnd,
        $purpose,
        $remarks,
        $pricePerDay,
        $totalPrice,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => (int)$pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
}
