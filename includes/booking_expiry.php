<?php
const BOOKING_EXPIRY_GRACE_MINUTES = 15;
const MISSED_PAYMENT_SUSPENSION_LIMIT = 3;

function suspend_users_with_missed_payments_pdo(PDO $pdo): int
{
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
    return $stmt->rowCount();
}

function suspend_users_with_missed_payments_mysqli(mysqli $conn): int
{
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
    return $conn->affected_rows;
}

function expire_stale_bookings_pdo(PDO $pdo): int
{
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
    suspend_users_with_missed_payments_pdo($pdo);
    return $expiredBookings;
}

function expire_stale_bookings_mysqli(mysqli $conn): int
{
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
    suspend_users_with_missed_payments_mysqli($conn);
    return $expiredBookings;
}
?>
