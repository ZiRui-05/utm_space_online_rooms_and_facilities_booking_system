<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin','facility_manager']);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? 'all';
$roomId = (int)($_GET['room_id'] ?? 0);
$facilityId = (int)($_GET['facility_id'] ?? 0);

if (strtotime($from) > strtotime($to)) { $tmp = $from; $from = $to; $to = $tmp; }

$validStatuses = ['all','pending','approved','rejected','cancelled','completed','expired'];
if (!in_array($status, $validStatuses, true)) $status = 'all';

$rooms = $conn->query('SELECT room_id, room_name, room_code FROM rooms ORDER BY room_name')->fetch_all(MYSQLI_ASSOC);
$facilities = $conn->query('SELECT facility_id, facility_name, facility_code FROM facilities ORDER BY facility_name')->fetch_all(MYSQLI_ASSOC);

function report_room_filter_label(array $room): string {
    $name = (string)($room['room_name'] ?? '');
    $code = (string)($room['room_code'] ?? $name);
    if (str_ends_with($code, '05') || str_ends_with($name, '05')) return $name . ', T05';
    if (str_ends_with($code, '06') || str_ends_with($name, '06')) return $name . ', T06';
    return $name;
}

$where = ['DATE(b.created_at) BETWEEN ? AND ?'];
$types = 'ss';
$params = [$from, $to];
if ($status !== 'all') { $where[] = 'b.booking_status = ?'; $types .= 's'; $params[] = $status; }
if ($roomId > 0) { $where[] = 'b.room_id = ?'; $types .= 'i'; $params[] = $roomId; }
if ($facilityId > 0) { $where[] = 'b.facility_id = ?'; $types .= 'i'; $params[] = $facilityId; }
$whereSql = implode(' AND ', $where);

function bind_report_params(mysqli_stmt $stmt, string $types, array &$params): void {
    $stmt->bind_param($types, ...$params);
}

$stmt = $conn->prepare("SELECT COUNT(*) total_bookings, COALESCE(SUM(b.total_price),0) total_amount, SUM(b.booking_status='pending') pending_count, SUM(b.booking_status='approved') approved_count, SUM(b.booking_status='rejected') rejected_count, SUM(b.payment_status='paid') paid_count FROM bookings b WHERE $whereSql");
bind_report_params($stmt, $types, $params); $stmt->execute(); $summary=$stmt->get_result()->fetch_assoc();
$stmt = $conn->prepare("SELECT b.booking_status, COUNT(*) total, COALESCE(SUM(b.total_price),0) amount FROM bookings b WHERE $whereSql GROUP BY b.booking_status ORDER BY b.booking_status");
bind_report_params($stmt, $types, $params); $stmt->execute(); $byStatus=$stmt->get_result();
$stmt = $conn->prepare("SELECT COALESCE(r.room_name,f.facility_name) resource_name, COALESCE(r.location,f.location) location, COUNT(*) total FROM bookings b LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE $whereSql GROUP BY resource_name, location ORDER BY total DESC LIMIT 10");
bind_report_params($stmt, $types, $params); $stmt->execute(); $popular=$stmt->get_result();
$stmt = $conn->prepare("SELECT b.*, u.full_name, u.email, COALESCE(r.room_name,f.facility_name) resource_name, COALESCE(r.location,f.location) location FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE $whereSql ORDER BY b.created_at DESC");
bind_report_params($stmt, $types, $params); $stmt->execute(); $details=$stmt->get_result();

$page_title='Admin Formal Booking Report'; $active_page='reports'; include __DIR__ . '/includes/header.php';
?>
<div class="no-print mb-8 flex justify-between items-end"><div><h1 class="text-4xl font-black text-[#36000f]">Generate Reports</h1><p class="text-slate-500 mt-2">Formal booking report with date, status, room and facility filters.</p></div><button onclick="window.print()" class="btn-warning">Print / Save PDF</button></div>
<form class="no-print bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
    <div><label class="text-sm font-bold text-slate-600">From Date</label><input class="input" type="date" name="from" value="<?= h($from) ?>"></div>
    <div><label class="text-sm font-bold text-slate-600">To Date</label><input class="input" type="date" name="to" value="<?= h($to) ?>"></div>
    <div><label class="text-sm font-bold text-slate-600">Status</label><select class="input" name="status"><option value="all">All Status</option><?php foreach(array_slice($validStatuses,1) as $s): ?><option value="<?= h($s) ?>" <?= $status===$s?'selected':'' ?>><?= h(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
    <div><label class="text-sm font-bold text-slate-600">Room</label><select class="input" name="room_id"><option value="0">All Rooms</option><?php foreach($rooms as $r): ?><option value="<?= h($r['room_id']) ?>" <?= $roomId===(int)$r['room_id']?'selected':'' ?>><?= h(report_room_filter_label($r)) ?></option><?php endforeach; ?></select></div>
    <div><label class="text-sm font-bold text-slate-600">Facility</label><select class="input" name="facility_id"><option value="0">All Facilities</option><?php foreach($facilities as $f): ?><option value="<?= h($f['facility_id']) ?>" <?= $facilityId===(int)$f['facility_id']?'selected':'' ?>><?= h($f['facility_name']) ?></option><?php endforeach; ?></select></div>
    <div class="flex gap-2"><button class="btn-primary flex-1">Generate</button><a class="btn-light" href="admin_reports.php">Reset</a></div>
</form>

<section class="print-card bg-white rounded-xl border border-[#dcc0c2] shadow-sm p-8">
    <div class="text-center border-b border-slate-300 pb-6 mb-6">
        <h1 class="text-3xl font-black text-[#36000f] uppercase">UTM SPACE Online Rooms and Facilities Booking System</h1>
        <h2 class="text-xl font-bold mt-2">Admin Formal Booking Report</h2>
        <p class="text-slate-600 mt-2">Report period: <b><?= date('d M Y', strtotime($from)) ?></b> to <b><?= date('d M Y', strtotime($to)) ?></b></p>
        <p class="text-slate-600 mt-1">Status: <b><?= h($status === 'all' ? 'All' : ucfirst($status)) ?></b></p>
    </div>
    <div class="grid md:grid-cols-2 gap-4 text-sm mb-6">
        <div><b>Generated by:</b> <?= h($user['full_name']) ?> (<?= h(ucwords(str_replace('_',' ', $user['role']))) ?>)</div>
        <div><b>Generated at:</b> <?= date('d M Y, h:i A') ?></div>
        <div><b>Report type:</b> Booking and resource usage report</div>
        <div><b>Date basis:</b> Booking created date</div>
    </div>
    <h3 class="text-lg font-black text-[#36000f] mb-3">1. Executive Summary</h3>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="border rounded-lg p-4"><p class="text-xs uppercase text-slate-500 font-bold">Total Bookings</p><p class="text-2xl font-black"><?= h($summary['total_bookings'] ?? 0) ?></p></div>
        <div class="border rounded-lg p-4"><p class="text-xs uppercase text-slate-500 font-bold">Pending</p><p class="text-2xl font-black"><?= h($summary['pending_count'] ?? 0) ?></p></div>
        <div class="border rounded-lg p-4"><p class="text-xs uppercase text-slate-500 font-bold">Approved</p><p class="text-2xl font-black"><?= h($summary['approved_count'] ?? 0) ?></p></div>
        <div class="border rounded-lg p-4"><p class="text-xs uppercase text-slate-500 font-bold">Rejected</p><p class="text-2xl font-black"><?= h($summary['rejected_count'] ?? 0) ?></p></div>
        <div class="border rounded-lg p-4"><p class="text-xs uppercase text-slate-500 font-bold">Total Amount</p><p class="text-2xl font-black">RM <?= number_format((float)($summary['total_amount'] ?? 0),2) ?></p></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div><h3 class="text-lg font-black text-[#36000f] mb-3">2. Booking Status Breakdown</h3><table class="w-full border"><thead><tr><th class="table-th">Status</th><th class="table-th">Total</th><th class="table-th">Amount</th></tr></thead><tbody><?php while($r=$byStatus->fetch_assoc()): ?><tr><td class="table-td"><span class="badge badge-<?= h($r['booking_status']) ?>"><?= h(ucfirst($r['booking_status'])) ?></span></td><td class="table-td font-bold"><?= h($r['total']) ?></td><td class="table-td">RM <?= number_format((float)$r['amount'],2) ?></td></tr><?php endwhile; ?></tbody></table></div>
        <div><h3 class="text-lg font-black text-[#36000f] mb-3">3. Top Used Rooms / Facilities</h3><table class="w-full border"><thead><tr><th class="table-th">Resource</th><th class="table-th">Location</th><th class="table-th">Bookings</th></tr></thead><tbody><?php while($r=$popular->fetch_assoc()): ?><tr><td class="table-td font-bold"><?= h($r['resource_name'] ?: 'Deleted resource') ?></td><td class="table-td"><?= h($r['location'] ?: '-') ?></td><td class="table-td"><?= h($r['total']) ?></td></tr><?php endwhile; ?></tbody></table></div>
    </div>
    <div class="page-break"></div>
    <h3 class="text-lg font-black text-[#36000f] mb-3">4. Detailed Booking List</h3>
    <table class="w-full border text-sm"><thead><tr><th class="table-th">ID</th><th class="table-th">Created Date</th><th class="table-th">Requester</th><th class="table-th">Room / Facility</th><th class="table-th">Booking Date/Time</th><th class="table-th">Status</th><th class="table-th">Payment</th><th class="table-th">Amount</th></tr></thead><tbody><?php while($b=$details->fetch_assoc()): ?><tr><td class="table-td">#<?= h($b['booking_id']) ?></td><td class="table-td"><?= h(date('d M Y', strtotime($b['created_at']))) ?></td><td class="table-td"><?= h($b['full_name']) ?><br><span class="text-xs"><?= h($b['email']) ?></span></td><td class="table-td"><?= h($b['resource_name']) ?><br><span class="text-xs"><?= h($b['location']) ?></span></td><td class="table-td"><?= h(date('d M Y h:i A', strtotime($b['booking_start']))) ?><br>to <?= h(date('d M Y h:i A', strtotime($b['booking_end']))) ?></td><td class="table-td"><?= h(ucfirst($b['booking_status'])) ?></td><td class="table-td"><?= h(ucfirst($b['payment_status'])) ?></td><td class="table-td">RM <?= number_format((float)$b['total_price'],2) ?></td></tr><?php endwhile; ?></tbody></table>
    <div class="mt-10 grid grid-cols-2 gap-10 text-sm">
        <div><p class="border-t border-slate-400 pt-2">Prepared by: <?= h($user['full_name']) ?></p></div>
        <div><p class="border-t border-slate-400 pt-2">Verified by: __________________________</p></div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
