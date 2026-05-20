<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin']);
$self_file = 'admin_booking_requests.php';
$edit_file = 'admin_edit_booking.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['decision'])) {
    $booking_id = (int)$_POST['booking_id'];
    $decision = $_POST['decision'] === 'approved' ? 'approved' : 'rejected';
    $remarks = trim($_POST['review_remarks'] ?? '');
    $stmt = $conn->prepare("UPDATE bookings SET booking_status=?, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=?");
    $stmt->bind_param('sisi', $decision, $user['user_id'], $remarks, $booking_id);
    $stmt->execute();
    $msg = $stmt->affected_rows ? "Booking status changed to $decision" : 'Booking not found or no changes made';
    header('Location: ' . $self_file . '?success=' . urlencode($msg)); exit;
}
$status = $_GET['status'] ?? 'all';
$where = $status !== 'all' ? "WHERE b.booking_status='" . $conn->real_escape_string($status) . "'" : '';
$list = $conn->query("SELECT b.*, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id $where ORDER BY b.created_at DESC");
$page_title='Admin Booking Requests'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex justify-between items-end">
    <div><h1 class="text-4xl font-black text-[#36000f]">Admin Booking Requests</h1><p class="text-slate-500 mt-2">Admin page for reviewing, approving, rejecting and editing booking requests.</p></div>
    <form><select name="status" onchange="this.form.submit()" class="input"><option value="all">All Status</option><?php foreach(['pending','approved','rejected','cancelled','completed','expired'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></form>
</div>
<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-hidden">
<table class="w-full"><thead><tr><th class="table-th">Booking</th><th class="table-th">Requester</th><th class="table-th">Resource</th><th class="table-th">Date/Time</th><th class="table-th">Payment</th><th class="table-th">Status</th><th class="table-th">Action</th></tr></thead><tbody>
<?php while($b=$list->fetch_assoc()): ?>
<tr>
<td class="table-td font-bold">#<?= h($b['booking_id']) ?><p class="text-xs text-slate-500"><?= h($b['purpose']) ?></p></td>
<td class="table-td"><?= h($b['full_name']) ?><p class="text-xs text-slate-500"><?= h($b['email']) ?></p></td>
<td class="table-td"><?= h($b['resource_name']) ?><p class="text-xs text-slate-500"><?= h($b['location']) ?></p></td>
<td class="table-td"><?= h($b['booking_start']) ?><p class="text-xs text-slate-500">to <?= h($b['booking_end']) ?></p></td>
<td class="table-td"><?= h($b['payment_status']) ?><p class="text-xs text-slate-500">RM <?= number_format((float)$b['total_price'],2) ?></p></td>
<td class="table-td"><span class="badge badge-<?= h($b['booking_status']) ?>"><?= h($b['booking_status']) ?></span><?php if(!empty($b['review_remarks'])): ?><p class="text-xs text-slate-500 mt-1">Remarks: <?= h($b['review_remarks']) ?></p><?php endif; ?></td>
<td class="table-td min-w-[240px]">
    <a class="btn-light text-xs py-2 mb-2" href="<?= h($edit_file) ?>?id=<?= h($b['booking_id']) ?>">Edit Booking</a>
    <form method="post" class="space-y-2"><input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>"><input name="review_remarks" class="input text-xs" placeholder="New review remarks"><div class="flex gap-2"><button name="decision" value="approved" class="bg-green-700 text-white px-3 py-2 rounded font-bold text-xs">Approve</button><button name="decision" value="rejected" class="bg-red-700 text-white px-3 py-2 rounded font-bold text-xs">Reject</button></div></form>
</td>
</tr>
<?php endwhile; ?>
</tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
