<?php
header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_once __DIR__ . '/../../includes/booking_validation.php';

$userId = (int)($_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0));
$resourceType = strtolower(trim($_POST['resource_type'] ?? 'facility'));
$resourceId = (int)($_POST['resource_id'] ?? 0);
$bookingDate = trim($_POST['booking_date'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');
$endTime = trim($_POST['end_time'] ?? '');
$remarks = trim($_POST['comments'] ?? '');
$purpose = trim($_POST['purpose'] ?? 'General booking request');
$spaceUtmDepartment = 'SPACE UTM (School of Professional and Continuing Education)';

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

try {

    $stmtProfile = $pdo->prepare(
        'SELECT full_name, utm_id, ic_no, phone_number, department, gender, address, role, verification_status, account_status FROM users WHERE user_id = ? LIMIT 1'
    );
    $stmtProfile->execute([$userId]);
    $profile = $stmtProfile->fetch(PDO::FETCH_ASSOC) ?: [];

    $accountStatus = strtolower((string)($profile['account_status'] ?? 'inactive'));
    $verificationStatus = strtolower((string)($profile['verification_status'] ?? 'unverified'));

    if ($accountStatus === 'suspended' || $accountStatus === 'suspend') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account is under suspend, kindly contact to admin for further help.']);
        exit;
    }

    if ($accountStatus !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account is not active. Please contact admin for further help.']);
        exit;
    }

    if ($verificationStatus !== 'verified') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only verified accounts can submit booking requests.']);
        exit;
    }

    $requiredProfileFields = [
        'full_name' => 'Full name',
        'utm_id' => 'UTM ID',
        'ic_no' => 'IC number',
        'phone_number' => 'Phone number',
        'department' => 'Department',
        'gender' => 'Gender',
        'address' => 'Address',
    ];

    $missingFields = [];
    foreach ($requiredProfileFields as $fieldKey => $label) {
        if (trim((string)($profile[$fieldKey] ?? '')) === '') {
            $missingFields[] = $label;
        }
    }

    if ($missingFields !== []) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please complete your profile before submitting a booking request',
            'missing_fields' => $missingFields,
        ]);
        exit;
    }

    $role = strtolower((string)($profile['role'] ?? 'guest'));
    $department = trim((string)($profile['department'] ?? ''));
    $isFree = $department === $spaceUtmDepartment;

    $selectedBookingDate = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate);
    $today = new DateTimeImmutable('today');
    $latestBookingDate = $today->modify($role === 'student' ? '+2 days' : '+30 days');
    $dateRangeMessage = $role === 'student'
        ? 'Booking date must be within 3 days including today'
        : 'Booking date must be within 30 days including today';

    if (
        !$selectedBookingDate ||
        $selectedBookingDate->format('Y-m-d') !== $bookingDate ||
        $selectedBookingDate < $today ||
        $selectedBookingDate > $latestBookingDate
    ) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $dateRangeMessage]);
        exit;
    }

    $selectedDayOfWeek = (int)$selectedBookingDate->format('N');
    if ($selectedDayOfWeek > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bookings are allowed on weekdays only']);
        exit;
    }

    if ($role === 'student' && ($endMinutes - $startMinutes) > (3 * 60)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Students can book up to 3 hours per session']);
        exit;
    }

    if ($role === 'student' && $resourceType === 'room') {
        $stmtExisting = $pdo->prepare(
            "SELECT COUNT(*) FROM bookings
             WHERE user_id = ?
               AND resource_type = 'room'
               AND booking_status IN ('pending', 'approved')"
        );
        $stmtExisting->execute([$userId]);
        $existingActiveOrPending = (int)$stmtExisting->fetchColumn();

        if ($existingActiveOrPending > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You already have a pending request or unreturned room booking']);
            exit;
        }
    }

    $pdo->beginTransaction();

    $availability = booking_validate_resource_availability_pdo($pdo, $resourceType, $resourceId, $bookingStart, $bookingEnd, null, true);
    if (!$availability['ok']) {
        $pdo->rollBack();
        http_response_code(str_contains($availability['message'], 'not found') ? 404 : 409);
        echo json_encode(['success' => false, 'message' => $availability['message']]);
        exit;
    }

    $resource = $availability['resource'];

    $pricePerDay = (float)($resource['price_per_day'] ?? 0);

    $totalPrice = $isFree ? 0.0 : $pricePerDay;
    $paymentStatus = $isFree ? 'paid' : 'unpaid';

    $roomId = $resourceType === 'room' ? $resourceId : null;
    $facilityId = $resourceType === 'facility' ? $resourceId : null;

    $sql = "INSERT INTO bookings
    (user_id, resource_type, room_id, facility_id, booking_start, booking_end, purpose, remarks, price_per_day, total_price, booking_status, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";

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
        $paymentStatus,
    ]);

    $newBookingId = (int)$pdo->lastInsertId();
    $pdo->commit();

    create_user_notification_pdo($pdo, $userId, $newBookingId, 'Booking request submitted', 'Your booking request #' . $newBookingId . ' has been submitted and is waiting for approval.', 'booking_request');

    $resourceDisplayName = trim((string)($resource['resource_name'] ?? ($resourceType === 'room' ? 'Room' : 'Facility')));
    $requesterName = trim((string)($profile['full_name'] ?? 'Unknown'));
    notify_staff_new_booking_pdo($pdo, $newBookingId, $resourceDisplayName, $requesterName);

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'booking_id' => $newBookingId,
        'total_price' => $totalPrice,
        'requires_payment' => $totalPrice > 0,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
}
