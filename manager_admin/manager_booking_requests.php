<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['facility_manager']);
$self_file = 'manager_booking_requests.php';
$edit_file = 'manager_edit_booking.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['decision'])) {
    $booking_id = (int)$_POST['booking_id'];
    $decision = $_POST['decision'] === 'approved' ? 'approved' : 'rejected';
    $remarks = trim($_POST['review_remarks'] ?? '');
    $stmt = $conn->prepare("UPDATE bookings SET booking_status=?, payment_status=CASE WHEN ?='rejected' AND payment_status IN ('paid','pending_verification') THEN 'refunded' ELSE payment_status END, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=? AND booking_status='pending'");
    $stmt->bind_param('ssisi', $decision, $decision, $user['user_id'], $remarks, $booking_id);
    $stmt->execute();
    $msg = $stmt->affected_rows ? 'Booking status changed to ' . $decision : 'Booking is no longer pending and cannot be changed from this list.';
    header('Location: ' . $self_file . '?success=' . urlencode($msg)); exit;
}
$status = $_GET['status'] ?? 'all';
$payment = $_GET['payment_status'] ?? 'all';
$date = trim($_GET['booking_date'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$where=[]; $types=''; $params=[];
if($status !== 'all'){ $where[]='b.booking_status=?'; $types.='s'; $params[]=$status; }
if($payment !== 'all'){ $where[]='b.payment_status=?'; $types.='s'; $params[]=$payment; }
if($date !== ''){ $where[]='DATE(b.booking_start)=?'; $types.='s'; $params[]=$date; }
$orderMap=['newest'=>'b.created_at DESC','oldest'=>'b.created_at ASC','date_asc'=>'b.booking_start ASC','date_desc'=>'b.booking_start DESC','price_high'=>'b.total_price DESC','price_low'=>'b.total_price ASC'];
$order=$orderMap[$sort] ?? $orderMap['newest'];
$sql="SELECT b.*, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id" . ($where ? ' WHERE '.implode(' AND ', $where) : '') . " ORDER BY $order";
$stmt=$conn->prepare($sql); if($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $list=$stmt->get_result();
$page_title='Facility Manager Booking Requests'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Facility Manager Booking Requests</h1><p class="text-slate-500 mt-2">Filter by booking date, payment status and sort order.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-5 gap-4">
    <select name="status" class="input"><option value="all">All Booking Status</option><?php foreach(['pending','approved','rejected','cancelled','completed','expired'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <select name="payment_status" class="input"><option value="all">All Payment Status</option><?php foreach(['unpaid','pending_verification','paid','payment_rejected','refunded'] as $p): ?><option value="<?= $p ?>" <?= $payment===$p?'selected':'' ?>><?= ucwords(str_replace('_',' ',$p)) ?></option><?php endforeach; ?></select>
    <input type="date" name="booking_date" class="input" value="<?= h($date) ?>">
    <select name="sort" class="input"><?php foreach(['newest'=>'Newest request','oldest'=>'Oldest request','date_asc'=>'Booking date ↑','date_desc'=>'Booking date ↓','price_high'=>'Price high → low','price_low'=>'Price low → high'] as $k=>$v): ?><option value="<?= $k ?>" <?= $sort===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select>
    <button class="btn-primary">Apply Filter</button>
</form>
<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Booking</th><th class="table-th">Requester</th><th class="table-th">Resource</th><th class="table-th">Date/Time</th><th class="table-th">Payment</th><th class="table-th">Status</th><th class="table-th">Action</th></tr></thead><tbody>
        <?php if($list->num_rows===0): ?><tr><td class="table-td text-center text-slate-500" colspan="7">No booking request found.</td></tr><?php endif; ?>
        <?php while($b=$list->fetch_assoc()): ?><tr><td class="table-td font-bold">#<?= h($b['booking_id']) ?><p class="text-xs text-slate-500"><?= h($b['purpose']) ?></p></td><td class="table-td"><?= h($b['full_name']) ?><p class="text-xs text-slate-500"><?= h($b['email']) ?></p></td><td class="table-td"><?= h($b['resource_name']) ?><p class="text-xs text-slate-500"><?= h($b['location']) ?></p></td><td class="table-td"><?= h($b['booking_start']) ?><p class="text-xs text-slate-500">to <?= h($b['booking_end']) ?></p></td><td class="table-td"><span class="badge badge-<?= h($b['payment_status']) ?>"><?= h(str_replace('_',' ',$b['payment_status'])) ?></span><p class="text-xs text-slate-500 mt-1">RM <?= number_format((float)$b['total_price'],2) ?></p></td><td class="table-td"><span class="badge badge-<?= h($b['booking_status']) ?>"><?= h($b['booking_status']) ?></span><?php if(!empty($b['review_remarks'])): ?><p class="text-xs text-slate-500 mt-1">Remarks: <?= h($b['review_remarks']) ?></p><?php endif; ?></td><td class="table-td min-w-[240px]"><a class="btn-light text-xs py-2 mb-2" href="<?= h($edit_file) ?>?id=<?= h($b['booking_id']) ?>">View Details</a><form method="post" class="space-y-2"><input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>"><input name="review_remarks" class="input text-xs" placeholder="New review remarks"><div class="flex gap-2"><button name="decision" value="approved" class="bg-green-700 text-white px-3 py-2 rounded font-bold text-xs">Approve</button><button name="decision" value="rejected" class="bg-red-700 text-white px-3 py-2 rounded font-bold text-xs">Reject</button></div></form></td></tr><?php endwhile; ?></tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
