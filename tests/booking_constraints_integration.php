<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/booking_constraints.php';
require_once __DIR__ . '/../includes/booking_validation.php';

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'utm_space_booking_system';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

function test_insert_booking(
    PDO $pdo,
    int $userId,
    int $roomId,
    string $bookingStart,
    string $bookingEnd
): int {
    $stmt = $pdo->prepare(
        "INSERT INTO bookings
         (user_id, request_fingerprint, resource_type, room_id, booking_start, booking_end,
          purpose, booking_status, payment_status)
         VALUES (?, ?, 'room', ?, ?, ?, 'Concurrency test', 'pending', 'unpaid')"
    );
    $stmt->execute([
        $userId,
        booking_request_fingerprint('room', $roomId, $bookingStart, $bookingEnd),
        $roomId,
        $bookingStart,
        $bookingEnd,
    ]);
    return (int)$pdo->lastInsertId();
}

function test_booking_row(
    int $bookingId,
    int $userId,
    int $roomId,
    string $bookingStart,
    string $bookingEnd
): array {
    return [
        'booking_id' => $bookingId,
        'user_id' => $userId,
        'resource_type' => 'room',
        'room_id' => $roomId,
        'facility_id' => null,
        'booking_start' => $bookingStart,
        'booking_end' => $bookingEnd,
    ];
}

function assert_constraint_code(callable $callback, string $expectedCode): void
{
    try {
        $callback();
    } catch (BookingConstraintException $error) {
        if ($error->constraintCode === $expectedCode) {
            return;
        }
        throw new RuntimeException(
            "Expected constraint {$expectedCode}, received {$error->constraintCode}.",
            0,
            $error
        );
    }
    throw new RuntimeException("Expected constraint {$expectedCode}, but the operation succeeded.");
}

$studentId = (int)$pdo->query(
    "SELECT user_id FROM users WHERE role = 'student' ORDER BY user_id LIMIT 1"
)->fetchColumn();
$otherUserIds = $pdo->query(
    "SELECT user_id FROM users WHERE user_id <> {$studentId} ORDER BY user_id LIMIT 2"
)->fetchAll(PDO::FETCH_COLUMN);
$roomIds = $pdo->query('SELECT room_id FROM rooms ORDER BY room_id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);

if ($studentId <= 0 || count($otherUserIds) < 2 || count($roomIds) < 2) {
    throw new RuntimeException('Integration test requires one student, two other users, and two rooms.');
}

$initialSlotCount = (int)$pdo->query('SELECT COUNT(*) FROM booking_resource_slots')->fetchColumn();
$initialStudentClaimCount = (int)$pdo->query('SELECT COUNT(*) FROM student_room_claims')->fetchColumn();

$pdo->beginTransaction();
try {
    $firstId = test_insert_booking($pdo, $studentId, (int)$roomIds[0], '2030-01-02 08:00:00', '2030-01-02 09:00:00');
    booking_acquire_claims_pdo(
        $pdo,
        test_booking_row($firstId, $studentId, (int)$roomIds[0], '2030-01-02 08:00:00', '2030-01-02 09:00:00'),
        'student'
    );

    $fingerprint = booking_request_fingerprint(
        'room',
        (int)$roomIds[0],
        '2030-01-02 08:00:00',
        '2030-01-02 09:00:00'
    );
    $existing = $pdo->prepare(
        "SELECT booking_id
         FROM bookings
         WHERE user_id = ?
           AND request_fingerprint = ?
           AND booking_status IN ('pending', 'approved')
         LIMIT 1"
    );
    $existing->execute([$studentId, $fingerprint]);
    if ((int)$existing->fetchColumn() !== $firstId) {
        throw new RuntimeException('An exact retry did not resolve to the original active booking.');
    }

    $availability = booking_validate_resource_availability_pdo(
        $pdo,
        'room',
        (int)$roomIds[0],
        '2030-01-02 08:00:00',
        '2030-01-02 09:00:00'
    );
    if ($availability['ok'] || ($availability['code'] ?? '') !== 'slot_conflict') {
        throw new RuntimeException('Availability validation did not return the structured slot conflict code.');
    }

    $secondId = test_insert_booking($pdo, $studentId, (int)$roomIds[1], '2030-01-02 10:00:00', '2030-01-02 11:00:00');
    assert_constraint_code(
        static function () use ($pdo, $secondId, $studentId, $roomIds): void {
            booking_acquire_claims_pdo(
                $pdo,
                test_booking_row($secondId, $studentId, (int)$roomIds[1], '2030-01-02 10:00:00', '2030-01-02 11:00:00'),
                'student'
            );
        },
        'student_room_limit'
    );

    booking_release_claims_pdo($pdo, $firstId);
    $released = $pdo->query(
        'SELECT COUNT(*) FROM booking_resource_slots WHERE booking_id = ' . $firstId
    )->fetchColumn();
    if ((int)$released !== 0) {
        throw new RuntimeException('Released bookings retained resource slot claims.');
    }
} finally {
    $pdo->rollBack();
}

$pdo->beginTransaction();
try {
    $firstUserId = (int)$otherUserIds[0];
    $secondUserId = (int)$otherUserIds[1];
    $roomId = (int)$roomIds[0];

    $firstId = test_insert_booking($pdo, $firstUserId, $roomId, '2030-01-03 12:00:00', '2030-01-03 13:00:00');
    booking_acquire_claims_pdo(
        $pdo,
        test_booking_row($firstId, $firstUserId, $roomId, '2030-01-03 12:00:00', '2030-01-03 13:00:00'),
        'staff'
    );

    $secondId = test_insert_booking($pdo, $secondUserId, $roomId, '2030-01-03 12:30:00', '2030-01-03 13:30:00');
    assert_constraint_code(
        static function () use ($pdo, $secondId, $secondUserId, $roomId): void {
            booking_acquire_claims_pdo(
                $pdo,
                test_booking_row($secondId, $secondUserId, $roomId, '2030-01-03 12:30:00', '2030-01-03 13:30:00'),
                'staff'
            );
        },
        'slot_conflict'
    );
} finally {
    $pdo->rollBack();
}

$slotCount = (int)$pdo->query('SELECT COUNT(*) FROM booking_resource_slots')->fetchColumn();
$studentClaimCount = (int)$pdo->query('SELECT COUNT(*) FROM student_room_claims')->fetchColumn();
if ($slotCount !== $initialSlotCount || $studentClaimCount !== $initialStudentClaimCount) {
    throw new RuntimeException('Constraint integration test left claim rows behind.');
}

echo "booking_constraints_integration: OK\n";
