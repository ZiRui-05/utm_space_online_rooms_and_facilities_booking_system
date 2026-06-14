<?php
if (!function_exists('booking_resource_meta')) {
    function booking_resource_meta(string $resourceType): ?array
    {
        if ($resourceType === 'room') {
            return [
                'table' => 'rooms',
                'id_col' => 'room_id',
                'name_col' => 'room_name',
            ];
        }

        if ($resourceType === 'facility') {
            return [
                'table' => 'facilities',
                'id_col' => 'facility_id',
                'name_col' => 'facility_name',
            ];
        }

        return null;
    }
}

if (!function_exists('booking_time_window_parts')) {
    function booking_time_window_parts(string $bookingStart, string $bookingEnd): ?array
    {
        $startTs = strtotime($bookingStart);
        $endTs = strtotime($bookingEnd);
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            return null;
        }

        return [
            'weekday' => (int)date('N', $startTs),
            'start_minutes' => ((int)date('G', $startTs) * 60) + (int)date('i', $startTs),
            'end_minutes' => ((int)date('G', $endTs) * 60) + (int)date('i', $endTs),
        ];
    }
}

if (!function_exists('booking_validation_result')) {
    function booking_validation_result(
        bool $ok,
        string $message = '',
        ?array $resource = null,
        string $code = ''
    ): array
    {
        return [
            'ok' => $ok,
            'message' => $message,
            'resource' => $resource,
            'code' => $code,
        ];
    }
}

if (!function_exists('booking_validate_resource_availability_pdo')) {
    function booking_validate_resource_availability_pdo(PDO $pdo, string $resourceType, int $resourceId, string $bookingStart, string $bookingEnd, ?int $excludeBookingId = null, bool $lockResource = false): array
    {
        $meta = booking_resource_meta($resourceType);
        $parts = booking_time_window_parts($bookingStart, $bookingEnd);
        if ($meta === null || $resourceId <= 0 || $parts === null) {
            return booking_validation_result(false, 'Invalid booking resource or time window.', null, 'invalid_booking');
        }

        $resourceSql = "SELECT price_per_day, resource_status, {$meta['name_col']} AS resource_name FROM {$meta['table']} WHERE {$meta['id_col']} = ? LIMIT 1";
        if ($lockResource) {
            $resourceSql .= ' FOR UPDATE';
        }
        $stmtResource = $pdo->prepare($resourceSql);
        $stmtResource->execute([$resourceId]);
        $resource = $stmtResource->fetch(PDO::FETCH_ASSOC);

        if (!$resource) {
            return booking_validation_result(false, 'Selected resource was not found.', null, 'resource_not_found');
        }

        $resourceStatus = strtolower((string)($resource['resource_status'] ?? 'unavailable'));
        if ($resourceStatus !== 'available') {
            return booking_validation_result(false, 'Selected resource is currently ' . $resourceStatus . ' and cannot be booked.', $resource, 'resource_unavailable');
        }

        $conflictSql = "SELECT booking_id, booking_status FROM bookings
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND booking_status IN ('pending', 'approved')
              AND booking_start < ?
              AND booking_end > ?";
        $conflictParams = [$resourceType, $resourceId, $bookingEnd, $bookingStart];
        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $conflictSql .= ' AND booking_id <> ?';
            $conflictParams[] = $excludeBookingId;
        }
        $conflictSql .= ' LIMIT 1';
        $stmtConflict = $pdo->prepare($conflictSql);
        $stmtConflict->execute($conflictParams);
        $conflictingBooking = $stmtConflict->fetch(PDO::FETCH_ASSOC);
        if ($conflictingBooking) {
            return booking_validation_result(false, 'Selected time slot is already ' . strtolower((string)$conflictingBooking['booking_status']) . '.', $resource, 'slot_conflict');
        }

        $scheduleSql = "SELECT status FROM schedules
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND status IN ('blocked', 'maintenance')
              AND start_time < ?
              AND end_time > ?
            LIMIT 1";
        $stmtSchedule = $pdo->prepare($scheduleSql);
        $stmtSchedule->execute([$resourceType, $resourceId, $bookingEnd, $bookingStart]);
        $conflictingSchedule = $stmtSchedule->fetch(PDO::FETCH_ASSOC);
        if ($conflictingSchedule) {
            return booking_validation_result(false, 'Selected time slot is ' . strtolower((string)$conflictingSchedule['status']) . '.', $resource, 'schedule_conflict');
        }

        $weeklySql = "SELECT status FROM weekly_schedule_rules
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND weekday = ?
              AND status IN ('blocked', 'maintenance')
              AND start_hour < ?
              AND end_hour > ?
            LIMIT 1";
        $stmtWeeklyRule = $pdo->prepare($weeklySql);
        $stmtWeeklyRule->execute([
            $resourceType,
            $resourceId,
            $parts['weekday'],
            (int)ceil($parts['end_minutes'] / 60),
            (int)floor($parts['start_minutes'] / 60),
        ]);
        $conflictingWeeklyRule = $stmtWeeklyRule->fetch(PDO::FETCH_ASSOC);
        if ($conflictingWeeklyRule) {
            return booking_validation_result(false, 'Selected time slot is ' . strtolower((string)$conflictingWeeklyRule['status']) . '.', $resource, 'schedule_conflict');
        }

        return booking_validation_result(true, '', $resource);
    }
}

if (!function_exists('booking_validate_resource_availability_mysqli')) {
    function booking_validate_resource_availability_mysqli(mysqli $conn, string $resourceType, int $resourceId, string $bookingStart, string $bookingEnd, ?int $excludeBookingId = null, bool $lockResource = false): array
    {
        $meta = booking_resource_meta($resourceType);
        $parts = booking_time_window_parts($bookingStart, $bookingEnd);
        if ($meta === null || $resourceId <= 0 || $parts === null) {
            return booking_validation_result(false, 'Invalid booking resource or time window.', null, 'invalid_booking');
        }

        $resourceSql = "SELECT price_per_day, resource_status, {$meta['name_col']} AS resource_name FROM {$meta['table']} WHERE {$meta['id_col']} = ? LIMIT 1";
        if ($lockResource) {
            $resourceSql .= ' FOR UPDATE';
        }
        $stmtResource = $conn->prepare($resourceSql);
        $stmtResource->bind_param('i', $resourceId);
        $stmtResource->execute();
        $resource = $stmtResource->get_result()->fetch_assoc();

        if (!$resource) {
            return booking_validation_result(false, 'Selected resource was not found.', null, 'resource_not_found');
        }

        $resourceStatus = strtolower((string)($resource['resource_status'] ?? 'unavailable'));
        if ($resourceStatus !== 'available') {
            return booking_validation_result(false, 'Selected resource is currently ' . $resourceStatus . ' and cannot be booked.', $resource, 'resource_unavailable');
        }

        $conflictSql = "SELECT booking_id, booking_status FROM bookings
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND booking_status IN ('pending', 'approved')
              AND booking_start < ?
              AND booking_end > ?";
        if ($excludeBookingId !== null && $excludeBookingId > 0) {
            $conflictSql .= ' AND booking_id <> ? LIMIT 1';
            $stmtConflict = $conn->prepare($conflictSql);
            $stmtConflict->bind_param('sissi', $resourceType, $resourceId, $bookingEnd, $bookingStart, $excludeBookingId);
        } else {
            $conflictSql .= ' LIMIT 1';
            $stmtConflict = $conn->prepare($conflictSql);
            $stmtConflict->bind_param('siss', $resourceType, $resourceId, $bookingEnd, $bookingStart);
        }
        $stmtConflict->execute();
        $conflictingBooking = $stmtConflict->get_result()->fetch_assoc();
        if ($conflictingBooking) {
            return booking_validation_result(false, 'Selected time slot is already ' . strtolower((string)$conflictingBooking['booking_status']) . '.', $resource, 'slot_conflict');
        }

        $scheduleSql = "SELECT status FROM schedules
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND status IN ('blocked', 'maintenance')
              AND start_time < ?
              AND end_time > ?
            LIMIT 1";
        $stmtSchedule = $conn->prepare($scheduleSql);
        $stmtSchedule->bind_param('siss', $resourceType, $resourceId, $bookingEnd, $bookingStart);
        $stmtSchedule->execute();
        $conflictingSchedule = $stmtSchedule->get_result()->fetch_assoc();
        if ($conflictingSchedule) {
            return booking_validation_result(false, 'Selected time slot is ' . strtolower((string)$conflictingSchedule['status']) . '.', $resource, 'schedule_conflict');
        }

        $weeklySql = "SELECT status FROM weekly_schedule_rules
            WHERE resource_type = ?
              AND {$meta['id_col']} = ?
              AND weekday = ?
              AND status IN ('blocked', 'maintenance')
              AND start_hour < ?
              AND end_hour > ?
            LIMIT 1";
        $weeklyEndHour = (int)ceil($parts['end_minutes'] / 60);
        $weeklyStartHour = (int)floor($parts['start_minutes'] / 60);
        $stmtWeeklyRule = $conn->prepare($weeklySql);
        $stmtWeeklyRule->bind_param('siiii', $resourceType, $resourceId, $parts['weekday'], $weeklyEndHour, $weeklyStartHour);
        $stmtWeeklyRule->execute();
        $conflictingWeeklyRule = $stmtWeeklyRule->get_result()->fetch_assoc();
        if ($conflictingWeeklyRule) {
            return booking_validation_result(false, 'Selected time slot is ' . strtolower((string)$conflictingWeeklyRule['status']) . '.', $resource, 'schedule_conflict');
        }

        return booking_validation_result(true, '', $resource);
    }
}

if (!function_exists('booking_active_resource_id')) {
    function booking_active_resource_id(array $booking): int
    {
        $resourceType = strtolower((string)($booking['resource_type'] ?? ''));
        return $resourceType === 'room'
            ? (int)($booking['room_id'] ?? 0)
            : (int)($booking['facility_id'] ?? 0);
    }
}

if (!function_exists('booking_payment_after_status_change')) {
    function booking_payment_after_status_change(string $bookingStatus, string $paymentStatus, float $totalPrice = 0.0): string
    {
        if ($totalPrice > 0 && in_array($bookingStatus, ['rejected', 'cancelled', 'expired'], true) && in_array($paymentStatus, ['paid', 'pending_verification'], true)) {
            return 'refunded';
        }

        if (in_array($bookingStatus, ['pending', 'approved'], true) && $paymentStatus === 'refunded') {
            return 'unpaid';
        }

        return $paymentStatus;
    }
}
?>
