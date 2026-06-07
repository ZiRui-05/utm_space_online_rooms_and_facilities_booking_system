<?php
require_once __DIR__ . '/notifications.php';
const BOOKING_EXPIRY_GRACE_MINUTES = 15;
const MISSED_PAYMENT_SUSPENSION_LIMIT = 3;

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
    return $expiredBookings;
}
?>
