<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/issue_reports_schema.php';
$user = require_role(['admin']);
ensure_issue_reports_table($conn);
$page_title = 'Admin Dashboard'; $active_page = 'dashboard';
$stats = [];
foreach ([
    'users' => 'SELECT COUNT(*) c FROM users',
    'pending_bookings' => "SELECT COUNT(*) c FROM bookings WHERE booking_status='pending'",
    'pending_issues' => "SELECT COUNT(*) c FROM issue_reports WHERE issue_status='pending' AND issue_hidden=0",
    'rooms' => 'SELECT COUNT(*) c FROM rooms',
    'facilities' => 'SELECT COUNT(*) c FROM facilities',
    'revenue' => "SELECT COALESCE(SUM(total_price),0) c FROM bookings WHERE payment_status='paid'",
    'pending_cards' => "SELECT COUNT(*) c FROM users WHERE utm_card_base64 IS NOT NULL AND utm_card_base64 <> '' AND utm_card_back_base64 IS NOT NULL AND utm_card_back_base64 <> '' AND verification_status='unverified'"
] as $key => $sql) { $stats[$key] = $conn->query($sql)->fetch_assoc()['c']; }
$recent = $conn->query("SELECT b.booking_id, b.booking_start, b.booking_status, u.full_name, COALESCE(CASE WHEN RIGHT(COALESCE(r.room_code, r.room_name), 2) = '05' THEN CONCAT(r.room_name, ', T05') WHEN RIGHT(COALESCE(r.room_code, r.room_name), 2) = '06' THEN CONCAT(r.room_name, ', T06') ELSE r.room_name END, f.facility_name) resource_name FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id ORDER BY b.created_at DESC LIMIT 6");
include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex justify-between items-end"><div><h1 class="text-4xl font-black text-[#36000f]">Admin Dashboard</h1><p class="text-slate-500 mt-2">Overall control for booking requests, users and reports.</p></div><a class="btn-warning" href="admin_reports.php">Generate Reports</a></div>
<div class="grid grid-cols-1 md:grid-cols-7 gap-5 mb-8">
<?php foreach ([['Users',$stats['users'],'group'],['Pending',$stats['pending_bookings'],'pending_actions'],['Pending Issues',$stats['pending_issues'],'report_problem'],['Rooms',$stats['rooms'],'meeting_room'],['Facilities',$stats['facilities'],'inventory'],['Paid Revenue','RM '.number_format((float)$stats['revenue'],2),'payments'],['Student Cards',$stats['pending_cards'],'badge']] as $card): ?>
<div class="bg-white p-5 rounded-xl border border-[#dcc0c2] shadow-sm"><span class="material-symbols-outlined text-red-900"><?= $card[2] ?></span><p class="text-xs uppercase tracking-widest text-slate-500 font-bold mt-3"><?= $card[0] ?></p><p class="text-2xl font-black text-[#36000f] mt-1"><?= $card[1] ?></p></div>
<?php endforeach; ?>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<div class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">Admin Actions</h2><div class="grid grid-cols-2 gap-3"><a class="btn-primary text-center" href="admin_booking_requests.php">View Booking Requests</a><a class="btn-primary text-center" href="admin_issue_reports.php">Review Issue Reports</a><a class="btn-primary text-center" href="admin_manage_users.php">Manage User Accounts</a><a class="btn-primary text-center" href="admin_verify_cards.php">Verify Student Card</a><a class="btn-primary text-center" href="admin_reports.php">Generate Reports</a></div></div>
<div class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm"><h2 class="text-xl font-black text-[#36000f] mb-4">Recent Bookings</h2><div class="space-y-3"><?php while($b=$recent->fetch_assoc()): ?><div class="flex justify-between border-b pb-3"><div><b><?= h($b['resource_name']) ?></b><p class="text-sm text-slate-500"><?= h($b['full_name']) ?> · <?= h($b['booking_start']) ?></p></div><span class="badge badge-<?= h($b['booking_status']) ?>"><?= h($b['booking_status']) ?></span></div><?php endwhile; ?></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
