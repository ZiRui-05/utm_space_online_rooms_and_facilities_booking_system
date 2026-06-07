<?php
session_start();
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../../config/db.php';
$userId = (int)$_SESSION['user']['user_id'];
$stmt = $pdo->prepare('SELECT issue_id, issue_title, issue_type, description, priority, issue_status, admin_remarks, created_at, updated_at FROM issue_reports WHERE reported_by = :user_id ORDER BY created_at DESC LIMIT 100');
$stmt->execute(['user_id' => $userId]);
$reports = $stmt->fetchAll();
function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Issue Report History - SPACEBOOK</title>
<style>:root{--primary:#8b1538;--border:#e5e7eb;--bg:#f5f5f5;--muted:#6b7280;--white:#fff}*{box-sizing:border-box;margin:0;padding:0}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:#333}.breadcrumb{padding:16px 30px;background:#fff;border-bottom:1px solid var(--border);font-size:13px}.breadcrumb a{color:var(--primary);text-decoration:none}.container{max-width:1150px;margin:30px auto;padding:0 24px}.card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow-x:auto}.head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:18px}.btn{background:var(--primary);color:#fff;text-decoration:none;border-radius:8px;padding:10px 14px;font-weight:700}table{width:100%;border-collapse:collapse;min-width:820px}th,td{padding:13px;border-bottom:1px solid var(--border);text-align:left;font-size:14px}th{background:#fafafa;font-size:12px;text-transform:uppercase;color:var(--muted)}.badge{border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;display:inline-block;background:#f3f4f6;color:#374151}.pending{background:#fff7ed;color:#c2410c}.resolved{background:#ecfdf5;color:#047857}.in_progress{background:#eff6ff;color:#1d4ed8}.rejected{background:#fef2f2;color:#b91c1c}.empty{text-align:center;color:var(--muted);padding:28px}</style></head><body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="breadcrumb"><a href="../../homepage.php">Home</a> / <a href="profile.php">Profile</a> / Issue Report History</div>
<div class="container"><div class="card"><div class="head"><div><h2>Issue Report History</h2><p style="color:var(--muted);font-size:14px;margin-top:6px;">Track all issues you submitted from your profile page.</p></div><a class="btn" href="profile.php">Back to Profile</a></div>
<table><thead><tr><th>ID</th><th>Issue</th><th>Type</th><th>Priority</th><th>Status</th><th>Admin Remarks</th><th>Submitted</th></tr></thead><tbody>
<?php if(!$reports): ?><tr><td colspan="7" class="empty">No issue reports submitted yet.</td></tr><?php endif; ?>
<?php foreach($reports as $r): ?><tr><td>#<?= h($r['issue_id']) ?></td><td><b><?= h($r['issue_title']) ?></b><p style="color:#6b7280;margin-top:5px;line-height:1.4;"><?= h($r['description']) ?></p></td><td><?= h(ucwords(str_replace('_',' ',$r['issue_type']))) ?></td><td><?= h(ucfirst($r['priority'])) ?></td><td><span class="badge <?= h($r['issue_status']) ?>"><?= h(ucwords(str_replace('_',' ',$r['issue_status']))) ?></span></td><td><?= h($r['admin_remarks'] ?: '-') ?></td><td><?= h(date('d M Y, h:i A', strtotime($r['created_at']))) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body></html>
