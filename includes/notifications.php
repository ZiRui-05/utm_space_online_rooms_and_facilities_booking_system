<?php
if (!function_exists('ensure_user_notifications_table_mysqli')) {
    function ensure_user_notifications_table_mysqli(mysqli $conn): void
    {
        $conn->query("CREATE TABLE IF NOT EXISTS user_notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            booking_id INT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            notification_type VARCHAR(40) NOT NULL DEFAULT 'account',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read_created (user_id, is_read, created_at),
            INDEX idx_booking (booking_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('ensure_user_notifications_table_pdo')) {
    function ensure_user_notifications_table_pdo(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            booking_id INT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            notification_type VARCHAR(40) NOT NULL DEFAULT 'account',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read_created (user_id, is_read, created_at),
            INDEX idx_booking (booking_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

if (!function_exists('create_user_notification_mysqli')) {
    function create_user_notification_mysqli(mysqli $conn, int $userId, ?int $bookingId, string $title, string $message, string $type = 'account'): void
    {
        if ($userId <= 0) return;
        ensure_user_notifications_table_mysqli($conn);
        $type = substr($type, 0, 40);
        $title = substr($title, 0, 150);
        $stmt = $conn->prepare('INSERT INTO user_notifications (user_id, booking_id, title, message, notification_type) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iisss', $userId, $bookingId, $title, $message, $type);
        $stmt->execute();
    }
}

if (!function_exists('create_user_notification_pdo')) {
    function create_user_notification_pdo(PDO $pdo, int $userId, ?int $bookingId, string $title, string $message, string $type = 'account'): void
    {
        if ($userId <= 0) return;
        ensure_user_notifications_table_pdo($pdo);
        $stmt = $pdo->prepare('INSERT INTO user_notifications (user_id, booking_id, title, message, notification_type) VALUES (:user_id, :booking_id, :title, :message, :notification_type)');
        $stmt->execute([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'title' => substr($title, 0, 150),
            'message' => $message,
            'notification_type' => substr($type, 0, 40),
        ]);
    }
}

if (!function_exists('notify_booking_status_change_mysqli')) {
    function notify_booking_status_change_mysqli(mysqli $conn, array $before, string $newStatus, string $newPaymentStatus): void
    {
        $oldStatus = (string)($before['booking_status'] ?? '');
        $oldPayment = (string)($before['payment_status'] ?? '');
        if ($oldStatus === $newStatus && $oldPayment === $newPaymentStatus) {
            return;
        }

        $bookingId = (int)($before['booking_id'] ?? 0);
        $userId = (int)($before['user_id'] ?? 0);
        if ($bookingId <= 0 || $userId <= 0) {
            return;
        }

        $resourceName = trim((string)($before['resource_name'] ?? 'your booking'));
        $statusLabel = ucfirst(str_replace('_', ' ', $newStatus));
        $paymentLabel = ucwords(str_replace('_', ' ', $newPaymentStatus));

        if ($newStatus === 'approved') {
            $title = 'Booking request approved';
            $message = 'Good news. Your booking request #' . $bookingId . ' for ' . $resourceName . ' has been approved. Payment status: ' . $paymentLabel . '.';
        } elseif ($newStatus === 'rejected') {
            $title = 'Booking request not approved';
            $message = 'Your booking request #' . $bookingId . ' for ' . $resourceName . ' was not approved. Payment status: ' . $paymentLabel . '.';
        } else {
            $title = 'Booking request status updated';
            $message = 'Your booking request #' . $bookingId . ' for ' . $resourceName . ' is now ' . $statusLabel . '. Payment status: ' . $paymentLabel . '.';
        }

        create_user_notification_mysqli($conn, $userId, $bookingId, $title, $message, 'booking_status');
    }
}
