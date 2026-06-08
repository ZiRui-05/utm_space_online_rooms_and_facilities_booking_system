<?php
require_once __DIR__ . '/notifications.php';
const BOOKING_EXPIRY_GRACE_MINUTES = 15;
const MISSED_PAYMENT_SUSPENSION_LIMIT = 3;
const RETURN_OVERDUE_GRACE_MINUTES = 10;
const RETURN_OVERDUE_SUSPENSION_LIMIT = 3;

function add_enum_value_to_booking_status_pdo(PDO $pdo, string $value): void
{
    $type = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'booking_status'")->fetchColumn();
    if (!is_string($type) || strpos($type, "'" . $value . "'") !== false) return;
    if (!preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches)) return;
    $values = [];
    foreach ($matches[1] as $enumValue) {
        $values[] = str_replace("\\'", "'", $enumValue);
    }
    $values[] = $value;
    $quoted = [];
    foreach (array_unique($values) as $enumValue) {
        $quoted[] = $pdo->quote($enumValue);
    }
    $pdo->exec("ALTER TABLE bookings MODIFY booking_status ENUM(" . implode(',', $quoted) . ") NOT NULL DEFAULT 'pending'");
}

function add_enum_value_to_booking_status_mysqli(mysqli $conn, string $value): void
{
    $result = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'booking_status'");
    $row = $result ? $result->fetch_assoc() : null;
    $type = (string)($row['COLUMN_TYPE'] ?? '');
    if ($type === '' || strpos($type, "'" . $value . "'") !== false) return;
    if (!preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches)) return;
    $values = [];
    foreach ($matches[1] as $enumValue) {
        $values[] = str_replace("\\'", "'", $enumValue);
    }
    $values[] = $value;
    $quoted = [];
    foreach (array_unique($values) as $enumValue) {
        $quoted[] = "'" . $conn->real_escape_string($enumValue) . "'";
    }
    $conn->query("ALTER TABLE bookings MODIFY booking_status ENUM(" . implode(',', $quoted) . ") NOT NULL DEFAULT 'pending'");
}

function ensure_return_overdue_schema_pdo(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    add_enum_value_to_booking_status_pdo($pdo, 'return_overdue');
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = ?");
    $colCheck->execute(['return_overdue_notified']);
    if ((int)$colCheck->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN return_overdue_notified TINYINT(1) NOT NULL DEFAULT 0");
    }
    $colCheck->execute(['return_overdue_at']);
    if ((int)$colCheck->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN return_overdue_at DATETIME NULL");
    }
    $done = true;
}

function ensure_return_overdue_schema_mysqli(mysqli $conn): void
{
    static $done = false;
    if ($done) return;
    add_enum_value_to_booking_status_mysqli($conn, 'return_overdue');
    $columns = ['return_overdue_notified' => "TINYINT(1) NOT NULL DEFAULT 0", 'return_overdue_at' => "DATETIME NULL"];
    foreach ($columns as $column => $definition) {
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = ?");
        $stmt->bind_param('s', $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ((int)($row['c'] ?? 0) === 0) {
            $conn->query("ALTER TABLE bookings ADD COLUMN {$column} {$definition}");
        }
    }
    $done = true;
}

function suspend_users_with_missed_payments_pdo(PDO $pdo): int
{
    $notifyStmt = $pdo->query("SELECT u.user_id FROM users u WHERE u.account_status = 'active' AND u.role IN ('student', 'staff') AND (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.total_price > 0 AND b.booking_status = 'expired' AND b.payment_status IN ('unpaid', 'payment_rejected')) >= " . MISSED_PAYMENT_SUSPENSION_LIMIT);
    $notifyUsers = $notifyStmt ? $notifyStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $stmt = $pdo->prepare(
        "UPDATE users u
         SET u.account_status = 'suspended'
         WHERE u.account_status = 'active'
           AND u.role IN ('student', 'staff')
           AND (
               SELECT COUNT(*)
               FROM bookings b
               WHERE b.user_id = u.user_id
                 AND b.total_price > 0
                 AND b.booking_status = 'expired'
                 AND b.payment_status IN ('unpaid', 'payment_rejected')
           ) >= " . MISSED_PAYMENT_SUSPENSION_LIMIT
    );
    $stmt->execute();
    $affected = $stmt->rowCount();
    if ($affected > 0) {
        foreach ($notifyUsers as $notifyUserId) {
            create_user_notification_pdo($pdo, (int)$notifyUserId, null, 'Account suspended', 'Your account was suspended after repeated expired unpaid bookings. Please contact admin for help.', 'account');
        }
    }
    return $affected;
}

function suspend_users_with_missed_payments_mysqli(mysqli $conn): int
{
    $notifyUsers = [];
    $notifyResult = $conn->query("SELECT u.user_id FROM users u WHERE u.account_status = 'active' AND u.role IN ('student', 'staff') AND (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.total_price > 0 AND b.booking_status = 'expired' AND b.payment_status IN ('unpaid', 'payment_rejected')) >= " . MISSED_PAYMENT_SUSPENSION_LIMIT);
    if ($notifyResult) {
        while ($row = $notifyResult->fetch_assoc()) $notifyUsers[] = (int)$row['user_id'];
    }
    $sql = "UPDATE users u
            SET u.account_status = 'suspended'
            WHERE u.account_status = 'active'
              AND u.role IN ('student', 'staff')
              AND (
                  SELECT COUNT(*)
                  FROM bookings b
                  WHERE b.user_id = u.user_id
                    AND b.total_price > 0
                    AND b.booking_status = 'expired'
                    AND b.payment_status IN ('unpaid', 'payment_rejected')
              ) >= " . MISSED_PAYMENT_SUSPENSION_LIMIT;
    $conn->query($sql);
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        foreach ($notifyUsers as $notifyUserId) {
            create_user_notification_mysqli($conn, (int)$notifyUserId, null, 'Account suspended', 'Your account was suspended after repeated expired unpaid bookings. Please contact admin for help.', 'account');
        }
    }
    return $affected;
}

function suspend_users_with_return_overdue_pdo(PDO $pdo): int
{
    ensure_return_overdue_schema_pdo($pdo);
    $notifyStmt = $pdo->query("SELECT u.user_id FROM users u WHERE u.account_status = 'active' AND u.role IN ('student', 'staff') AND (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.return_overdue_at IS NOT NULL AND b.return_overdue_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) >= " . RETURN_OVERDUE_SUSPENSION_LIMIT);
    $notifyUsers = $notifyStmt ? $notifyStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $stmt = $pdo->prepare(
        "UPDATE users u
         SET u.account_status = 'suspended'
         WHERE u.account_status = 'active'
           AND u.role IN ('student', 'staff')
           AND (
               SELECT COUNT(*)
               FROM bookings b
               WHERE b.user_id = u.user_id
                 AND b.return_overdue_at IS NOT NULL
                 AND b.return_overdue_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
           ) >= " . RETURN_OVERDUE_SUSPENSION_LIMIT
    );
    $stmt->execute();
    $affected = $stmt->rowCount();
    if ($affected > 0) {
        foreach ($notifyUsers as $notifyUserId) {
            create_user_notification_pdo($pdo, (int)$notifyUserId, null, 'Account suspended', 'Your account was suspended after 3 late room/facility returns within 1 month. Please contact admin for help.', 'account');
        }
    }
    return $affected;
}

function suspend_users_with_return_overdue_mysqli(mysqli $conn): int
{
    ensure_return_overdue_schema_mysqli($conn);
    $notifyUsers = [];
    $notifyResult = $conn->query("SELECT u.user_id FROM users u WHERE u.account_status = 'active' AND u.role IN ('student', 'staff') AND (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.return_overdue_at IS NOT NULL AND b.return_overdue_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) >= " . RETURN_OVERDUE_SUSPENSION_LIMIT);
    if ($notifyResult) {
        while ($row = $notifyResult->fetch_assoc()) $notifyUsers[] = (int)$row['user_id'];
    }
    $sql = "UPDATE users u
            SET u.account_status = 'suspended'
            WHERE u.account_status = 'active'
              AND u.role IN ('student', 'staff')
              AND (
                  SELECT COUNT(*)
                  FROM bookings b
                  WHERE b.user_id = u.user_id
                    AND b.return_overdue_at IS NOT NULL
                    AND b.return_overdue_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
              ) >= " . RETURN_OVERDUE_SUSPENSION_LIMIT;
    $conn->query($sql);
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        foreach ($notifyUsers as $notifyUserId) {
            create_user_notification_mysqli($conn, (int)$notifyUserId, null, 'Account suspended', 'Your account was suspended after 3 late room/facility returns within 1 month. Please contact admin for help.', 'account');
        }
    }
    return $affected;
}

function process_return_overdue_bookings_pdo(PDO $pdo): int
{
    ensure_return_overdue_schema_pdo($pdo);
    $stmt = $pdo->query(
        "SELECT b.booking_id, b.user_id, b.booking_end,
                u.full_name,
                COALESCE(r.room_name, f.facility_name, 'Resource') resource_name
         FROM bookings b
         JOIN users u ON u.user_id = b.user_id
         LEFT JOIN rooms r ON r.room_id = b.room_id
         LEFT JOIN facilities f ON f.facility_id = b.facility_id
         WHERE b.booking_status = 'approved'
           AND b.return_overdue_notified = 0
           AND b.booking_end <= DATE_SUB(NOW(), INTERVAL " . RETURN_OVERDUE_GRACE_MINUTES . " MINUTE)"
    );
    $bookings = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $processed = 0;
    foreach ($bookings as $booking) {
        $bookingId = (int)$booking['booking_id'];
        $update = $pdo->prepare("UPDATE bookings SET booking_status = 'return_overdue', return_overdue_notified = 1, return_overdue_at = COALESCE(return_overdue_at, NOW()) WHERE booking_id = ? AND booking_status = 'approved' AND return_overdue_notified = 0");
        $update->execute([$bookingId]);
        if ($update->rowCount() <= 0) continue;
        $processed++;
        notify_staff_return_overdue_pdo(
            $pdo,
            $bookingId,
            (string)$booking['resource_name'],
            (string)$booking['full_name'],
            date('d M Y, h:i A', strtotime((string)$booking['booking_end']))
        );
    }
    suspend_users_with_return_overdue_pdo($pdo);
    return $processed;
}

function process_return_overdue_bookings_mysqli(mysqli $conn): int
{
    ensure_return_overdue_schema_mysqli($conn);
    $result = $conn->query(
        "SELECT b.booking_id, b.user_id, b.booking_end,
                u.full_name,
                COALESCE(r.room_name, f.facility_name, 'Resource') resource_name
         FROM bookings b
         JOIN users u ON u.user_id = b.user_id
         LEFT JOIN rooms r ON r.room_id = b.room_id
         LEFT JOIN facilities f ON f.facility_id = b.facility_id
         WHERE b.booking_status = 'approved'
           AND b.return_overdue_notified = 0
           AND b.booking_end <= DATE_SUB(NOW(), INTERVAL " . RETURN_OVERDUE_GRACE_MINUTES . " MINUTE)"
    );
    $bookings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) $bookings[] = $row;
    }
    $processed = 0;
    foreach ($bookings as $booking) {
        $bookingId = (int)$booking['booking_id'];
        $update = $conn->prepare("UPDATE bookings SET booking_status = 'return_overdue', return_overdue_notified = 1, return_overdue_at = COALESCE(return_overdue_at, NOW()) WHERE booking_id = ? AND booking_status = 'approved' AND return_overdue_notified = 0");
        $update->bind_param('i', $bookingId);
        $update->execute();
        if ($update->affected_rows <= 0) continue;
        $processed++;
        notify_staff_return_overdue_mysqli(
            $conn,
            $bookingId,
            (string)$booking['resource_name'],
            (string)$booking['full_name'],
            date('d M Y, h:i A', strtotime((string)$booking['booking_end']))
        );
    }
    suspend_users_with_return_overdue_mysqli($conn);
    return $processed;
}

function expire_stale_bookings_pdo(PDO $pdo): int
{
    $expiringStmt = $pdo->query("SELECT booking_id, user_id FROM bookings WHERE booking_status IN ('pending', 'approved') AND booking_start <= DATE_SUB(NOW(), INTERVAL " . BOOKING_EXPIRY_GRACE_MINUTES . " MINUTE) AND (booking_status = 'pending' OR (total_price > 0 AND payment_status <> 'paid'))");
    $expiringBookings = $expiringStmt ? $expiringStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $stmt = $pdo->prepare(
        "UPDATE bookings
         SET booking_status = 'expired',
             payment_status = CASE
                 WHEN payment_status IN ('paid', 'pending_verification') THEN 'refunded'
                 ELSE payment_status
             END
         WHERE booking_status IN ('pending', 'approved')
           AND booking_start <= DATE_SUB(NOW(), INTERVAL " . BOOKING_EXPIRY_GRACE_MINUTES . " MINUTE)
           AND (
               booking_status = 'pending'
               OR (total_price > 0 AND payment_status <> 'paid')
           )"
    );
    $stmt->execute();
    $expiredBookings = $stmt->rowCount();
    if ($expiredBookings > 0) {
        foreach ($expiringBookings as $booking) {
            create_user_notification_pdo($pdo, (int)$booking['user_id'], (int)$booking['booking_id'], 'Booking expired', 'Your booking request #' . (int)$booking['booking_id'] . ' has expired.', 'booking_status');
        }
    }
    suspend_users_with_missed_payments_pdo($pdo);
    process_return_overdue_bookings_pdo($pdo);
    return $expiredBookings;
}

function expire_stale_bookings_mysqli(mysqli $conn): int
{
    $expiringBookings = [];
    $expiringResult = $conn->query("SELECT booking_id, user_id FROM bookings WHERE booking_status IN ('pending', 'approved') AND booking_start <= DATE_SUB(NOW(), INTERVAL " . BOOKING_EXPIRY_GRACE_MINUTES . " MINUTE) AND (booking_status = 'pending' OR (total_price > 0 AND payment_status <> 'paid'))");
    if ($expiringResult) {
        while ($row = $expiringResult->fetch_assoc()) $expiringBookings[] = $row;
    }
    $sql = "UPDATE bookings
            SET booking_status = 'expired',
                payment_status = CASE
                    WHEN payment_status IN ('paid', 'pending_verification') THEN 'refunded'
                    ELSE payment_status
                END
            WHERE booking_status IN ('pending', 'approved')
              AND booking_start <= DATE_SUB(NOW(), INTERVAL " . BOOKING_EXPIRY_GRACE_MINUTES . " MINUTE)
              AND (
                  booking_status = 'pending'
                  OR (total_price > 0 AND payment_status <> 'paid')
              )";
    $conn->query($sql);
    $expiredBookings = $conn->affected_rows;
    if ($expiredBookings > 0) {
        foreach ($expiringBookings as $booking) {
            create_user_notification_mysqli($conn, (int)$booking['user_id'], (int)$booking['booking_id'], 'Booking expired', 'Your booking request #' . (int)$booking['booking_id'] . ' has expired.', 'booking_status');
        }
    }
    suspend_users_with_missed_payments_mysqli($conn);
    process_return_overdue_bookings_mysqli($conn);
    return $expiredBookings;
}
?>
