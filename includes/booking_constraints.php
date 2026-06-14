<?php

if (!class_exists('BookingConstraintException')) {
    final class BookingConstraintException extends RuntimeException
    {
        public string $constraintCode;

        public function __construct(string $constraintCode, string $message, ?Throwable $previous = null)
        {
            parent::__construct($message, 0, $previous);
            $this->constraintCode = $constraintCode;
        }
    }
}

if (!function_exists('booking_is_active_status')) {
    function booking_is_active_status(string $status): bool
    {
        return in_array($status, ['pending', 'approved'], true);
    }
}

if (!function_exists('booking_request_fingerprint')) {
    function booking_request_fingerprint(
        string $resourceType,
        int $resourceId,
        string $bookingStart,
        string $bookingEnd
    ): string {
        return hash('sha256', implode('|', [
            strtolower(trim($resourceType)),
            (string)$resourceId,
            date('Y-m-d H:i:s', strtotime($bookingStart)),
            date('Y-m-d H:i:s', strtotime($bookingEnd)),
        ]));
    }
}

if (!function_exists('booking_claim_slot_starts')) {
    function booking_claim_slot_starts(string $bookingStart, string $bookingEnd): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $bookingStart);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $bookingEnd);
        if (!$start || !$end || $end <= $start) {
            throw new BookingConstraintException('invalid_time_window', 'Invalid booking time window.');
        }

        if (
            ((int)$start->format('i') % 15) !== 0 ||
            ((int)$end->format('i') % 15) !== 0 ||
            $start->format('s') !== '00' ||
            $end->format('s') !== '00'
        ) {
            throw new BookingConstraintException('invalid_time_window', 'Booking times must use 15-minute units.');
        }

        $slots = [];
        for ($slot = $start; $slot < $end; $slot = $slot->modify('+15 minutes')) {
            $slots[] = $slot->format('Y-m-d H:i:s');
        }
        return $slots;
    }
}

if (!function_exists('booking_active_resource_id')) {
    function booking_active_resource_id(array $booking): int
    {
        return strtolower((string)($booking['resource_type'] ?? '')) === 'room'
            ? (int)($booking['room_id'] ?? 0)
            : (int)($booking['facility_id'] ?? 0);
    }
}

if (!function_exists('booking_lock_context_pdo')) {
    function booking_lock_context_pdo(PDO $pdo, int $bookingId): ?array
    {
        $lookup = $pdo->prepare(
            'SELECT booking_id, user_id, resource_type, room_id, facility_id
             FROM bookings WHERE booking_id = ? LIMIT 1'
        );
        $lookup->execute([$bookingId]);
        $booking = $lookup->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            return null;
        }

        $userLock = $pdo->prepare('SELECT user_id, role FROM users WHERE user_id = ? LIMIT 1 FOR UPDATE');
        $userLock->execute([(int)$booking['user_id']]);
        $user = $userLock->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }

        $resourceType = (string)$booking['resource_type'];
        $resourceId = booking_active_resource_id($booking);
        $resourceTable = $resourceType === 'room' ? 'rooms' : 'facilities';
        $resourceIdColumn = $resourceType === 'room' ? 'room_id' : 'facility_id';
        $resourceLock = $pdo->prepare("SELECT {$resourceIdColumn} FROM {$resourceTable} WHERE {$resourceIdColumn} = ? LIMIT 1 FOR UPDATE");
        $resourceLock->execute([$resourceId]);
        if (!$resourceLock->fetchColumn()) {
            return null;
        }

        $booking['role'] = (string)$user['role'];
        return $booking;
    }
}

if (!function_exists('booking_lock_context_mysqli')) {
    function booking_lock_context_mysqli(mysqli $conn, int $bookingId): ?array
    {
        $lookup = $conn->prepare(
            'SELECT booking_id, user_id, resource_type, room_id, facility_id
             FROM bookings WHERE booking_id = ? LIMIT 1'
        );
        $lookup->bind_param('i', $bookingId);
        $lookup->execute();
        $booking = $lookup->get_result()->fetch_assoc();
        if (!$booking) {
            return null;
        }

        $userId = (int)$booking['user_id'];
        $userLock = $conn->prepare('SELECT user_id, role FROM users WHERE user_id = ? LIMIT 1 FOR UPDATE');
        $userLock->bind_param('i', $userId);
        $userLock->execute();
        $user = $userLock->get_result()->fetch_assoc();
        if (!$user) {
            return null;
        }

        $resourceType = (string)$booking['resource_type'];
        $resourceId = booking_active_resource_id($booking);
        $resourceTable = $resourceType === 'room' ? 'rooms' : 'facilities';
        $resourceIdColumn = $resourceType === 'room' ? 'room_id' : 'facility_id';
        $resourceLock = $conn->prepare("SELECT {$resourceIdColumn} FROM {$resourceTable} WHERE {$resourceIdColumn} = ? LIMIT 1 FOR UPDATE");
        $resourceLock->bind_param('i', $resourceId);
        $resourceLock->execute();
        if (!$resourceLock->get_result()->fetch_assoc()) {
            return null;
        }

        $booking['role'] = (string)$user['role'];
        return $booking;
    }
}

if (!function_exists('booking_release_claims_pdo')) {
    function booking_release_claims_pdo(PDO $pdo, int $bookingId): void
    {
        $stmt = $pdo->prepare('DELETE FROM booking_resource_slots WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        $stmt = $pdo->prepare('DELETE FROM student_room_claims WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
    }
}

if (!function_exists('booking_release_claims_mysqli')) {
    function booking_release_claims_mysqli(mysqli $conn, int $bookingId): void
    {
        $stmt = $conn->prepare('DELETE FROM booking_resource_slots WHERE booking_id = ?');
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $stmt = $conn->prepare('DELETE FROM student_room_claims WHERE booking_id = ?');
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
    }
}

if (!function_exists('booking_acquire_claims_pdo')) {
    function booking_acquire_claims_pdo(PDO $pdo, array $booking, string $role): void
    {
        $bookingId = (int)($booking['booking_id'] ?? 0);
        $userId = (int)($booking['user_id'] ?? 0);
        $resourceType = (string)($booking['resource_type'] ?? '');
        $resourceId = booking_active_resource_id($booking);
        if ($bookingId <= 0 || $userId <= 0 || $resourceId <= 0) {
            throw new BookingConstraintException('invalid_booking', 'Booking constraint data is incomplete.');
        }

        booking_release_claims_pdo($pdo, $bookingId);

        if ($role === 'student' && $resourceType === 'room') {
            try {
                $studentClaim = $pdo->prepare(
                    'INSERT INTO student_room_claims (user_id, booking_id) VALUES (?, ?)'
                );
                $studentClaim->execute([$userId, $bookingId]);
            } catch (PDOException $error) {
                if ((int)($error->errorInfo[1] ?? 0) === 1062) {
                    throw new BookingConstraintException(
                        'student_room_limit',
                        'You already have a pending request or unreturned room booking.',
                        $error
                    );
                }
                throw $error;
            }
        }

        try {
            $slotClaim = $pdo->prepare(
                'INSERT INTO booking_resource_slots
                 (resource_type, resource_id, slot_start, booking_id)
                 VALUES (?, ?, ?, ?)'
            );
            foreach (booking_claim_slot_starts((string)$booking['booking_start'], (string)$booking['booking_end']) as $slotStart) {
                $slotClaim->execute([$resourceType, $resourceId, $slotStart, $bookingId]);
            }
        } catch (PDOException $error) {
            $driverCode = (int)($error->errorInfo[1] ?? 0);
            if ($driverCode === 1062) {
                throw new BookingConstraintException(
                    'slot_conflict',
                    'Selected time slot is already pending or approved.',
                    $error
                );
            }
            throw $error;
        }
    }
}

if (!function_exists('booking_acquire_claims_mysqli')) {
    function booking_acquire_claims_mysqli(mysqli $conn, array $booking, string $role): void
    {
        $bookingId = (int)($booking['booking_id'] ?? 0);
        $userId = (int)($booking['user_id'] ?? 0);
        $resourceType = (string)($booking['resource_type'] ?? '');
        $resourceId = booking_active_resource_id($booking);
        if ($bookingId <= 0 || $userId <= 0 || $resourceId <= 0) {
            throw new BookingConstraintException('invalid_booking', 'Booking constraint data is incomplete.');
        }

        booking_release_claims_mysqli($conn, $bookingId);

        if ($role === 'student' && $resourceType === 'room') {
            try {
                $studentClaim = $conn->prepare(
                    'INSERT INTO student_room_claims (user_id, booking_id) VALUES (?, ?)'
                );
                $studentClaim->bind_param('ii', $userId, $bookingId);
                $studentClaim->execute();
            } catch (mysqli_sql_exception $error) {
                if ((int)$error->getCode() === 1062) {
                    throw new BookingConstraintException(
                        'student_room_limit',
                        'You already have a pending request or unreturned room booking.',
                        $error
                    );
                }
                throw $error;
            }
        }

        try {
            $slotClaim = $conn->prepare(
                'INSERT INTO booking_resource_slots
                 (resource_type, resource_id, slot_start, booking_id)
                 VALUES (?, ?, ?, ?)'
            );
            foreach (booking_claim_slot_starts((string)$booking['booking_start'], (string)$booking['booking_end']) as $slotStart) {
                $slotClaim->bind_param('sisi', $resourceType, $resourceId, $slotStart, $bookingId);
                $slotClaim->execute();
            }
        } catch (mysqli_sql_exception $error) {
            if ((int)$error->getCode() === 1062) {
                throw new BookingConstraintException(
                    'slot_conflict',
                    'Selected time slot is already pending or approved.',
                    $error
                );
            }
            throw $error;
        }
    }
}

if (!function_exists('booking_cleanup_inactive_claims_pdo')) {
    function booking_cleanup_inactive_claims_pdo(PDO $pdo): void
    {
        $pdo->exec(
            "DELETE slots FROM booking_resource_slots slots
             JOIN bookings b ON b.booking_id = slots.booking_id
             WHERE b.booking_status NOT IN ('pending', 'approved')"
        );
        $pdo->exec(
            "DELETE claims FROM student_room_claims claims
             JOIN bookings b ON b.booking_id = claims.booking_id
             WHERE b.booking_status NOT IN ('pending', 'approved')"
        );
        $pdo->exec(
            "UPDATE bookings SET request_fingerprint = NULL
             WHERE booking_status NOT IN ('pending', 'approved')
               AND request_fingerprint IS NOT NULL"
        );
    }
}

if (!function_exists('booking_cleanup_inactive_claims_mysqli')) {
    function booking_cleanup_inactive_claims_mysqli(mysqli $conn): void
    {
        $conn->query(
            "DELETE slots FROM booking_resource_slots slots
             JOIN bookings b ON b.booking_id = slots.booking_id
             WHERE b.booking_status NOT IN ('pending', 'approved')"
        );
        $conn->query(
            "DELETE claims FROM student_room_claims claims
             JOIN bookings b ON b.booking_id = claims.booking_id
             WHERE b.booking_status NOT IN ('pending', 'approved')"
        );
        $conn->query(
            "UPDATE bookings SET request_fingerprint = NULL
             WHERE booking_status NOT IN ('pending', 'approved')
               AND request_fingerprint IS NOT NULL"
        );
    }
}

if (!function_exists('booking_is_retryable_database_error')) {
    function booking_is_retryable_database_error(Throwable $error): bool
    {
        $code = (int)$error->getCode();
        if ($error instanceof PDOException) {
            $code = (int)($error->errorInfo[1] ?? $code);
        }
        return in_array($code, [1205, 1213], true);
    }
}
