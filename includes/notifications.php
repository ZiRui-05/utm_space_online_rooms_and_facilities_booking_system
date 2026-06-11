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

if (!function_exists('notify_staff_new_booking_pdo')) {
    function notify_staff_new_booking_pdo(PDO $pdo, int $bookingId, string $resourceName, string $requesterName): void
    {
        if ($bookingId <= 0) return;
        ensure_user_notifications_table_pdo($pdo);
        $stmt = $pdo->query("SELECT user_id FROM users WHERE role IN ('admin','facility_manager') AND account_status='active'");
        if (!$stmt) return;
        $title   = 'New booking request';
        $message = 'New booking request #' . $bookingId . ' from ' . $requesterName . ' for ' . $resourceName . ' is pending your review.';
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $staffId) {
            create_user_notification_pdo($pdo, (int)$staffId, $bookingId, $title, $message, 'booking_request');
        }
    }
}

if (!function_exists('notify_staff_return_overdue_pdo')) {
    function notify_staff_return_overdue_pdo(PDO $pdo, int $bookingId, string $resourceName, string $requesterName, string $endedAt): void
    {
        if ($bookingId <= 0) return;
        ensure_user_notifications_table_pdo($pdo);
        $stmt = $pdo->query("SELECT user_id FROM users WHERE role IN ('admin','facility_manager') AND account_status='active'");
        if (!$stmt) return;
        $title = 'Booking return overdue';
        $message = 'Booking #' . $bookingId . ' for ' . $resourceName . ' by ' . $requesterName . ' ended at ' . $endedAt . ' and has not been confirmed returned after 10 minutes.';
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $staffId) {
            create_user_notification_pdo($pdo, (int)$staffId, $bookingId, $title, $message, 'return_overdue');
        }
    }
}

if (!function_exists('notify_staff_return_overdue_mysqli')) {
    function notify_staff_return_overdue_mysqli(mysqli $conn, int $bookingId, string $resourceName, string $requesterName, string $endedAt): void
    {
        if ($bookingId <= 0) return;
        ensure_user_notifications_table_mysqli($conn);
        $result = $conn->query("SELECT user_id FROM users WHERE role IN ('admin','facility_manager') AND account_status='active'");
        if (!$result) return;
        $title = 'Booking return overdue';
        $message = 'Booking #' . $bookingId . ' for ' . $resourceName . ' by ' . $requesterName . ' ended at ' . $endedAt . ' and has not been confirmed returned after 10 minutes.';
        while ($row = $result->fetch_assoc()) {
            create_user_notification_mysqli($conn, (int)$row['user_id'], $bookingId, $title, $message, 'return_overdue');
        }
    }
}

if (!function_exists('notify_staff_new_booking_mysqli')) {
    function notify_staff_new_booking_mysqli(mysqli $conn, int $bookingId, string $resourceName, string $requesterName): void
    {
        if ($bookingId <= 0) return;
        ensure_user_notifications_table_mysqli($conn);
        $result = $conn->query("SELECT user_id FROM users WHERE role IN ('admin','facility_manager') AND account_status='active'");
        if (!$result) return;
        $title   = 'New booking request';
        $message = 'New booking request #' . $bookingId . ' from ' . $requesterName . ' for ' . $resourceName . ' is pending your review.';
        while ($row = $result->fetch_assoc()) {
            create_user_notification_mysqli($conn, (int)$row['user_id'], $bookingId, $title, $message, 'booking_request');
        }
    }
}

if (!function_exists('notify_booking_status_change_mysqli')) {
    function notify_booking_status_change_mysqli(mysqli $conn, array $before, string $newStatus, string $newPaymentStatus): array
    {
        $result = [
            'notification_created' => false,
            'email_attempted' => false,
            'email_success' => false,
            'email_message' => '',
            'email_request_id' => '',
        ];
        $oldStatus = (string)($before['booking_status'] ?? '');
        $oldPayment = (string)($before['payment_status'] ?? '');
        if ($oldStatus === $newStatus && $oldPayment === $newPaymentStatus) {
            return $result;
        }

        $bookingId = (int)($before['booking_id'] ?? 0);
        $userId = (int)($before['user_id'] ?? 0);
        if ($bookingId <= 0 || $userId <= 0) {
            return $result;
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
        } elseif ($newStatus === 'return_overdue') {
            $title = 'Booking return overdue';
            $message = 'Your booking #' . $bookingId . ' for ' . $resourceName . ' has been marked return overdue. Please contact admin or the facility manager if this is incorrect. Payment status: ' . $paymentLabel . '.';
        } else {
            $title = 'Booking request status updated';
            $message = 'Your booking request #' . $bookingId . ' for ' . $resourceName . ' is now ' . $statusLabel . '. Payment status: ' . $paymentLabel . '.';
        }

        create_user_notification_mysqli($conn, $userId, $bookingId, $title, $message, 'booking_status');
        $result['notification_created'] = true;

        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            $toEmail = trim((string)($before['email'] ?? ''));
            $toName = trim((string)($before['full_name'] ?? ''));

            if ($toEmail === '' || $toName === '') {
                $userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE user_id = ? LIMIT 1');
                $userStmt->bind_param('i', $userId);
                $userStmt->execute();
                $recipient = $userStmt->get_result()->fetch_assoc();
                if ($recipient) {
                    $toEmail = $toEmail !== '' ? $toEmail : trim((string)($recipient['email'] ?? ''));
                    $toName = $toName !== '' ? $toName : trim((string)($recipient['full_name'] ?? ''));
                }
            }

            if ($toEmail !== '' && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $result['email_attempted'] = true;
                require_once __DIR__ . '/../config/mailer.php';
                $emailSubject = 'Booking Request Approved';
                $emailText = "Hello " . ($toName !== '' ? $toName : 'there') . ",\n\n"
                    . "Your booking request #{$bookingId} for {$resourceName} has been approved.\n"
                    . "Payment status: {$paymentLabel}.\n\n"
                    . "Please sign in to UTM Space Booking for the latest booking and payment details.\n\n"
                    . "Thank you.";
                $emailHtml = '<p>Hello ' . htmlspecialchars($toName !== '' ? $toName : 'there', ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p>Your booking request <strong>#' . $bookingId . '</strong> for <strong>' . htmlspecialchars($resourceName, ENT_QUOTES, 'UTF-8') . '</strong> has been approved.</p>'
                    . '<p><strong>Payment status:</strong> ' . htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>Please sign in to UTM Space Booking for the latest booking and payment details.</p>'
                    . '<p>Thank you.</p>';
                $sendResult = sendMail($toEmail, $toName, $emailSubject, $emailText, $emailHtml);
                $result['email_success'] = (bool)$sendResult['success'];
                $result['email_message'] = (string)$sendResult['message'];
                $result['email_request_id'] = (string)($sendResult['request_id'] ?? '');
                if (!$sendResult['success']) {
                    error_log('Booking approval email failed for booking #' . $bookingId . ': ' . $sendResult['message']);
                }
            } else {
                $result['email_message'] = 'No valid recipient email address';
            }
        }

        return $result;
    }
}
