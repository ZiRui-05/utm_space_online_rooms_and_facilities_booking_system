<?php
const BOOKING_EXPIRY_GRACE_MINUTES = 15;

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
    return $stmt->rowCount();
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
    return $conn->affected_rows;
}
?>
