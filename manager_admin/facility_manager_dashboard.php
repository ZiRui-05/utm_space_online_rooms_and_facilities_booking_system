<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/issue_reports_schema.php';
$user = require_role(['facility_manager']);
ensure_issue_reports_table($conn);
$page_title = 'Facility Manager Dashboard'; $active_page = 'dashboard';
$today = date('Y-m-d');
$pending = $conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='pending'")->fetch_assoc()['c'];
$pendingIssues = $conn->query("SELECT COUNT(*) c FROM issue_reports WHERE issue_status='pending'")->fetch_assoc()['c'];
$approvedToday = $conn->query("SELECT COUNT(*) c FROM bookings WHERE booking_status='approved' AND DATE(booking_start)='$today'")->fetch_assoc()['c'];
$maintenance = $conn->query("SELECT (SELECT COUNT(*) FROM rooms WHERE resource_status='maintenance') + (SELECT COUNT(*) FROM facilities WHERE resource_status='maintenance') c")->fetch_assoc()['c'];
$next = $conn->query("SELECT b.*, u.full_name, COALESCE(CASE WHEN RIGHT(COALESCE(r.room_code, r.room_name), 2) = '05' THEN CONCAT(r.room_name, ', T05') WHEN RIGHT(COALESCE(r.room_code, r.room_name), 2) = '06' THEN CONCAT(r.room_name, ', T06') ELSE r.room_name END, f.facility_name) resource_name FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_status IN ('pending','approved') ORDER BY b.booking_start ASC LIMIT 8");
include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4"><div><h1 class="text-4xl font-black text-[#36000f]">Facility Manager Dashboard</h1><p class="text-slate-500 mt-2">Daily operations for rooms, facilities, schedules and booking approval.</p></div><a class="btn-warning" href="manager_issue_reports.php">Submit Issue Report</a></div>
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
<div class="bg-white p-6 rounded-xl border border-[#dcc0c2]"><p class="text-xs uppercase tracking-widest text-slate-500 font-bold">Pending Requests</p><p class="text-4xl font-black text-[#36000f]"><?= h($pending) ?></p></div>
<div class="bg-[#ffddb4] p-6 rounded-xl border border-[#dcc0c2]"><p class="text-xs uppercase tracking-widest text-[#633f00] font-bold">Approved Today</p><p class="text-4xl font-black text-[#291800]"><?= h($approvedToday) ?></p></div>
<div class="bg-white p-6 rounded-xl border border-[#dcc0c2]"><p class="text-xs uppercase tracking-widest text-slate-500 font-bold">Under Maintenance</p><p class="text-4xl font-black text-[#36000f]"><?= h($maintenance) ?></p></div>
<div class="bg-white p-6 rounded-xl border border-[#dcc0c2]"><p class="text-xs uppercase tracking-widest text-slate-500 font-bold">Pending Issues</p><p class="text-4xl font-black text-[#36000f]"><?= h($pendingIssues) ?></p></div>
</div>
<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-hidden"><div class="p-5 flex justify-between items-center"><h2 class="text-xl font-black text-[#36000f]">Upcoming / Pending Bookings</h2><a href="manager_booking_requests.php" class="btn-warning">Review Requests</a></div><table class="w-full"><thead><tr><th class="table-th">Resource</th><th class="table-th">User</th><th class="table-th">Start</th><th class="table-th">End</th><th class="table-th">Status</th></tr></thead><tbody><?php while($b=$next->fetch_assoc()): ?><tr><td class="table-td font-bold"><?= h($b['resource_name']) ?></td><td class="table-td"><?= h($b['full_name']) ?></td><td class="table-td"><?= h($b['booking_start']) ?></td><td class="table-td"><?= h($b['booking_end']) ?></td><td class="table-td"><span class="badge badge-<?= h($b['booking_status']) ?>"><?= h($b['booking_status']) ?></span></td></tr><?php endwhile; ?></tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
