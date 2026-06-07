<?php
session_start();
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../../config/db.php';
$userId = (int)$_SESSION['user']['user_id'];

$pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(40) NOT NULL DEFAULT 'booking_status',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read_created (user_id, is_read, created_at),
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all_read') {
    $stmt = $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    header('Location: all_notifications.php');
    exit;
}

$stmt = $pdo->prepare('SELECT notification_id, booking_id, title, message, notification_type, is_read, created_at FROM user_notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 100');
$stmt->execute(['user_id' => $userId]);
$notifications = $stmt->fetchAll();
$unreadCount = 0;
foreach ($notifications as $n) {
    if ((int)$n['is_read'] === 0) $unreadCount++;
}
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Notifications - SPACEBOOK</title>
<style>
:root{--primary:#8b1538;--border:#e5e7eb;--bg:#f5f5f5;--text:#333;--muted:#6b7280;--white:#fff;--success:#047857}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text)}
.breadcrumb{padding:16px 30px;background:#fff;border-bottom:1px solid var(--border);font-size:13px}.breadcrumb a{color:var(--primary);text-decoration:none}
.container{max-width:1050px;margin:30px auto;padding:0 24px}.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}.header{padding:22px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap}.header h2{color:#2b0010}.header p{font-size:14px;color:var(--muted);margin-top:6px}.btn{border:0;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;padding:10px 14px;cursor:pointer}.list{display:flex;flex-direction:column}.item{padding:18px 24px;border-bottom:1px solid var(--border);display:grid;grid-template-columns:14px 1fr auto;gap:14px;align-items:start}.dot{width:10px;height:10px;border-radius:50%;background:var(--primary);margin-top:7px}.item.read .dot{background:#d1d5db}.title{font-weight:800;color:#2b0010}.message{font-size:14px;color:#4b5563;margin-top:5px;line-height:1.5}.meta{font-size:12px;color:var(--muted);white-space:nowrap}.badge{display:inline-block;font-size:11px;border-radius:999px;background:#fff7ed;color:#9a3412;padding:4px 9px;margin-top:8px}.empty{padding:34px;text-align:center;color:var(--muted)}
@media(max-width:700px){.item{grid-template-columns:12px 1fr}.meta{grid-column:2;white-space:normal}.container{padding:0 14px}}
</style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="breadcrumb"><a href="../../homepage.php">Home</a> / <a href="profile.php">Profile</a> / All Notifications</div>
<div class="container"><div class="card">
<div class="header"><div><h2>All Notifications</h2><p><?= h($unreadCount) ?> unread update<?= $unreadCount === 1 ? '' : 's' ?>. Booking status changes will appear here.</p></div><form method="post"><input type="hidden" name="action" value="mark_all_read"><button class="btn" type="submit">Mark All Read</button></form></div>
<div class="list">
<?php if (!$notifications): ?><div class="empty">No notifications yet.</div><?php endif; ?>
<?php foreach ($notifications as $n): ?>
<div class="item <?= (int)$n['is_read'] === 1 ? 'read' : '' ?>"><div class="dot"></div><div><div class="title"><?= h($n['title']) ?></div><div class="message"><?= h($n['message']) ?></div><?php if(!empty($n['booking_id'])): ?><span class="badge">Booking #<?= h($n['booking_id']) ?></span><?php endif; ?></div><div class="meta"><?= h(date('d M Y, h:i A', strtotime($n['created_at']))) ?></div></div>
<?php endforeach; ?>
</div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body></html>
