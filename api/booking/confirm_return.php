<?php
/**
 * Email action endpoint: user clicks "Confirm Room Returned" from reminder email.
 * Validates HMAC token, marks booking as completed, renders a confirmation page.
 * No session required — the signed token is the proof of identity.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/notifications.php';

$appSecret = (string)(getenv('APP_SECRET') ?: 'utm-space-booking-secret-key-2024');

$bookingId = (int)($_GET['bid'] ?? 0);
$userId    = (int)($_GET['uid'] ?? 0);
$expiry    = (int)($_GET['exp'] ?? 0);
$token     = (string)($_GET['token'] ?? '');

function renderPage(string $status, string $heading, string $message, int $bookingId = 0): void
{
    $colors = [
        'success' => ['#dcfce7', '#166534', '&#10003;'],
        'error'   => ['#fee2e2', '#991b1b', '&#33;'],
        'info'    => ['#eff6ff', '#1e40af', '&#8505;'],
    ];
    [$bg, $fg, $icon] = $colors[$status] ?? $colors['info'];
    $bookingLine = $bookingId > 0
        ? "<p style='color:#64748b;font-size:13px;margin:8px 0 0;'>Booking #{$bookingId}</p>"
        : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Room Return — SPACEBOOK</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f8f9ff;margin:0;padding:30px 16px;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;">
  <div style="max-width:480px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #dcc0c2;box-shadow:0 2px 12px rgba(0,0,0,.08);">
    <div style="background:#36000f;padding:22px 24px;text-align:center;">
      <h1 style="color:#ffffff;margin:0;font-size:22px;letter-spacing:3px;">SPACEBOOK</h1>
      <p style="color:#ffcdd2;margin:5px 0 0;font-size:12px;">UTM Space Online Booking System</p>
    </div>
    <div style="padding:36px 32px;text-align:center;">
      <div style="width:60px;height:60px;border-radius:50%;background:{$bg};color:{$fg};
                  font-size:28px;font-weight:bold;display:inline-flex;align-items:center;
                  justify-content:center;margin-bottom:20px;">{$icon}</div>
      <h2 style="color:#1e293b;margin:0 0 10px;font-size:20px;">{$heading}</h2>
      <p style="color:#374151;font-size:14px;line-height:1.6;margin:0;">{$message}</p>
      {$bookingLine}
    </div>
    <div style="background:#f8f9ff;padding:16px;text-align:center;border-top:1px solid #dcc0c2;">
      <p style="color:#94a3b8;font-size:12px;margin:0;">UTM SPACEBOOK — Automated confirmation</p>
    </div>
  </div>
</body>
</html>
HTML;
}

// ── Validate inputs ─────────────────────────────────────────────────────────
if ($bookingId <= 0 || $userId <= 0 || $expiry <= 0 || $token === '') {
    renderPage('error', 'Invalid Link', 'This confirmation link is incomplete or malformed. Please contact admin if you need help.');
    exit;
}

// ── Check expiry ────────────────────────────────────────────────────────────
if (time() > $expiry) {
    renderPage('error', 'Link Expired', 'This confirmation link has expired. If you have already returned the room, no further action is needed.', $bookingId);
    exit;
}

// ── Verify HMAC token ───────────────────────────────────────────────────────
$expected = hash_hmac('sha256', "{$bookingId}:{$userId}:{$expiry}", $appSecret);
if (!hash_equals($expected, $token)) {
    renderPage('error', 'Invalid Token', 'This confirmation link is invalid. Please do not modify the URL. Contact admin if you need help.');
    exit;
}

// ── Fetch booking ───────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT b.booking_id, b.user_id, b.booking_status, b.resource_type,
            COALESCE(r.room_name, f.facility_name, 'Resource') AS resource_name
     FROM bookings b
     LEFT JOIN rooms r ON r.room_id = b.room_id
     LEFT JOIN facilities f ON f.facility_id = b.facility_id
     WHERE b.booking_id = ? AND b.user_id = ?
     LIMIT 1"
);
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    renderPage('error', 'Booking Not Found', 'This booking could not be found. Please contact admin for help.');
    exit;
}

$resourceName = (string)($booking['resource_name'] ?? 'Resource');

// Already completed/cancelled
if ((string)$booking['booking_status'] === 'completed') {
    renderPage('info', 'Already Confirmed', "Booking #{$bookingId} for <strong>{$resourceName}</strong> has already been marked as returned. Thank you!", $bookingId);
    exit;
}

if (!in_array((string)$booking['booking_status'], ['approved', 'expired', 'return_overdue'], true)) {
    renderPage('info', 'Nothing to Confirm', "Booking #{$bookingId} is currently <em>" . htmlspecialchars((string)$booking['booking_status'], ENT_QUOTES, 'UTF-8') . "</em> and does not require a return confirmation.", $bookingId);
    exit;
}

// ── Mark as completed ───────────────────────────────────────────────────────
$update = $pdo->prepare("UPDATE bookings SET booking_status = 'completed' WHERE booking_id = ? AND user_id = ?");
$update->execute([$bookingId, $userId]);

// In-app notification to user
create_user_notification_pdo(
    $pdo,
    $userId,
    $bookingId,
    'Room return confirmed',
    "Your return for booking #{$bookingId} ({$resourceName}) has been recorded. Thank you!",
    'booking_status'
);

renderPage(
    'success',
    'Room Returned — Thank You!',
    "Booking #{$bookingId} for <strong>" . htmlspecialchars($resourceName, ENT_QUOTES, 'UTF-8') . "</strong> has been marked as <strong>completed</strong>. We hope you had a productive session!",
    $bookingId
);
