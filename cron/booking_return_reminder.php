<?php
/**
 * Booking Return Reminder Cron Script
 *
 * Sends an email reminder to users 10 minutes before their approved booking ends.
 * Rooms get an action button so the user can confirm return via email.
 * Facilities show an informational reminder only (in-person handover required).
 *
 * Schedule: run every 1–2 minutes via Windows Task Scheduler or cron.
 * Example Task Scheduler command:
 *   php C:\xampp\htdocs\utm_space_online_rooms_and_facilities_booking_system\cron\booking_return_reminder.php
 *
 * Web access during development (optional):
 *   http://localhost/utm_space_online_rooms_and_facilities_booking_system/cron/booking_return_reminder.php?secret=YOUR_CRON_WEB_SECRET
 */

if (php_sapi_name() !== 'cli') {
    $expected = trim((string)(getenv('CRON_WEB_SECRET') ?: ''));
    $provided = trim((string)($_GET['secret'] ?? ''));
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Forbidden. Set CRON_WEB_SECRET env var to allow browser access.');
    }
}

define('CRON_ROOT', dirname(__DIR__));

require_once CRON_ROOT . '/config/db.php';   // provides $pdo
require_once CRON_ROOT . '/config/mailer.php';
require_once CRON_ROOT . '/includes/notifications.php';

function reminder_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    echo $line . PHP_EOL;

    $logDirectory = CRON_ROOT . '/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
        error_log('Unable to create reminder log directory: ' . $logDirectory);
        return;
    }

    file_put_contents($logDirectory . '/booking_return_reminder.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$lockStmt = $pdo->query("SELECT GET_LOCK('utm_space_booking_return_reminder', 0)");
if ((int)$lockStmt->fetchColumn() !== 1) {
    reminder_log('Another reminder process is already running; skipped.');
    exit(0);
}
register_shutdown_function(static function () use ($pdo): void {
    try {
        $pdo->query("SELECT RELEASE_LOCK('utm_space_booking_return_reminder')");
    } catch (Throwable $e) {
        error_log('Unable to release booking reminder lock: ' . $e->getMessage());
    }
});

// ── Migration: add return_reminder_sent column if missing ───────────────────
$colCheck = $pdo->query(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'bookings'
       AND COLUMN_NAME  = 'return_reminder_sent'"
);
if ((int)$colCheck->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN return_reminder_sent TINYINT(1) NOT NULL DEFAULT 0");
    reminder_log('[migration] Added return_reminder_sent column to bookings.');
}

// ── Fetch bookings that end in 8–12 minutes, not yet reminded ───────────────
$stmt = $pdo->prepare(
    "SELECT b.booking_id, b.user_id, b.resource_type, b.booking_start, b.booking_end,
            u.email, u.full_name,
            COALESCE(r.room_name, '')     AS room_name,
            COALESCE(f.facility_name, '') AS facility_name
     FROM bookings b
     JOIN users u ON u.user_id = b.user_id
     LEFT JOIN rooms r ON r.room_id = b.room_id
     LEFT JOIN facilities f ON f.facility_id = b.facility_id
     WHERE b.booking_status      = 'approved'
       AND b.return_reminder_sent = 0
       AND b.booking_end BETWEEN DATE_ADD(NOW(), INTERVAL 8 MINUTE)
                             AND DATE_ADD(NOW(), INTERVAL 12 MINUTE)"
);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($bookings)) {
    reminder_log('No upcoming bookings to remind.');
    exit(0);
}

$appUrl    = rtrim((string)(getenv('APP_URL') ?: 'http://localhost/utm_space_online_rooms_and_facilities_booking_system'), '/');
$appSecret = trim((string)(getenv('APP_SECRET') ?: ''));
if ($appSecret === '') {
    reminder_log('APP_SECRET is not configured; reminder links cannot be signed.');
    exit(1);
}

foreach ($bookings as $booking) {
    $bookingId   = (int)$booking['booking_id'];
    $userId      = (int)$booking['user_id'];
    $resourceType = (string)$booking['resource_type'];
    $endTime     = (string)$booking['booking_end'];

    $resourceName = $resourceType === 'room'
        ? (string)$booking['room_name']
        : (string)$booking['facility_name'];

    $userName  = (string)$booking['full_name'];
    $userEmail = (string)$booking['email'];

    // Generate signed token for room return confirmation
    $actionButton = '';
    if ($resourceType === 'room') {
        $expiry   = strtotime($endTime) + 3600; // valid 1 hour after booking ends
        $payload  = "{$bookingId}:{$userId}:{$expiry}";
        $token    = hash_hmac('sha256', $payload, $appSecret);
        $confirmUrl = $appUrl . '/api/booking/confirm_return.php'
            . '?bid=' . $bookingId
            . '&uid=' . $userId
            . '&exp=' . $expiry
            . '&token=' . urlencode($token);

        $actionButton = '
        <div style="text-align:center;margin:28px 0 8px;">
            <a href="' . htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8') . '"
               style="background:#36000f;color:#ffffff;padding:14px 32px;border-radius:8px;
                      text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">
                &#10003; Confirm Room Returned
            </a>
        </div>
        <p style="color:#94a3b8;font-size:12px;text-align:center;margin:0 0 24px;">
            Click once you have vacated the room and returned access.
        </p>';
    } else {
        $actionButton = '
        <p style="color:#374151;font-size:14px;background:#f0fdf4;border:1px solid #bbf7d0;
                  border-radius:8px;padding:14px 18px;margin:20px 0 24px;">
            &#128274; Please return this facility to the responsible staff in person before your booking ends.
        </p>';
    }

    $formattedEnd = date('d M Y, h:i A', strtotime($endTime));

    $checklist = $resourceType === 'room'
        ? '<li>Remove all personal belongings</li>
           <li>Leave the room in its original condition</li>
           <li>Turn off all lights and equipment</li>
           <li>Ensure doors are properly closed</li>'
        : '<li>Remove all personal belongings</li>
           <li>Return the facility in its original condition</li>
           <li>Hand over access to the responsible staff</li>';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="font-family:Arial,sans-serif;background:#f8f9ff;margin:0;padding:20px;">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;
              overflow:hidden;border:1px solid #dcc0c2;box-shadow:0 2px 8px rgba(0,0,0,.06);">

    <!-- Header -->
    <div style="background:#36000f;padding:28px 24px;text-align:center;">
      <h1 style="color:#ffffff;margin:0;font-size:26px;letter-spacing:3px;">SPACEBOOK</h1>
      <p style="color:#ffcdd2;margin:6px 0 0;font-size:13px;">UTM Space Online Booking System</p>
    </div>

    <!-- Body -->
    <div style="padding:32px 28px;">
      <h2 style="color:#36000f;margin-top:0;font-size:20px;">&#9200; Booking Ending in 10 Minutes</h2>
      <p style="color:#374151;font-size:15px;">Dear <strong>{$userName}</strong>,</p>
      <p style="color:#374151;font-size:14px;line-height:1.6;">
        Your booking for <strong>{$resourceName}</strong> is ending in approximately <strong>10 minutes</strong>.
        Please begin wrapping up now.
      </p>

      <!-- Booking details card -->
      <div style="background:#f8f9ff;border:1px solid #dcc0c2;border-radius:8px;padding:16px 20px;margin:20px 0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="color:#64748b;font-size:13px;padding:5px 0;width:40%;">Booking #</td>
            <td style="font-weight:bold;font-size:13px;color:#1e293b;">{$bookingId}</td>
          </tr>
          <tr>
            <td style="color:#64748b;font-size:13px;padding:5px 0;">Resource</td>
            <td style="font-weight:bold;font-size:13px;color:#1e293b;">{$resourceName}</td>
          </tr>
          <tr>
            <td style="color:#64748b;font-size:13px;padding:5px 0;">End Time</td>
            <td style="font-weight:bold;font-size:13px;color:#1e293b;">{$formattedEnd}</td>
          </tr>
        </table>
      </div>

      <p style="color:#374151;font-size:14px;">Before leaving, please ensure:</p>
      <ul style="color:#374151;font-size:14px;line-height:1.8;">{$checklist}</ul>

      {$actionButton}
    </div>

    <!-- Footer -->
    <div style="background:#f8f9ff;padding:16px 28px;text-align:center;border-top:1px solid #dcc0c2;">
      <p style="color:#94a3b8;font-size:12px;margin:0;">
        This is an automated reminder from UTM SPACEBOOK. Do not reply to this email.
      </p>
    </div>
  </div>
</body>
</html>
HTML;

    $textBody = "Booking Ending in 10 Minutes\n\n"
        . "Dear {$userName},\n\n"
        . "Your booking #{$bookingId} for {$resourceName} ends at {$formattedEnd}.\n\n"
        . "Please ensure you vacate and return the " . ($resourceType === 'room' ? 'room' : 'facility') . " on time.\n\n"
        . "— UTM SPACEBOOK";

    $subject = "⏰ Reminder: Your booking for {$resourceName} ends in 10 minutes";

    $result = sendMail($userEmail, $userName, $subject, $textBody, $htmlBody);

    if ($result['success']) {
        // Mark as reminded
        $markStmt = $pdo->prepare("UPDATE bookings SET return_reminder_sent = 1 WHERE booking_id = ?");
        $markStmt->execute([$bookingId]);
        reminder_log("Reminder sent for booking #{$bookingId}; mail request {$result['request_id']}.");
    } else {
        reminder_log("Failed to send reminder for booking #{$bookingId}; mail request "
            . ($result['request_id'] ?? 'unknown') . '; code ' . ($result['code'] ?? 'unknown') . '.');
    }
}

reminder_log('Done. Processed ' . count($bookings) . ' booking(s).');
