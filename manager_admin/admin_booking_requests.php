<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/booking_validation.php';
require_once __DIR__ . '/../includes/booking_constraints.php';
$user = require_role(['admin']);
$self_file = 'admin_booking_requests.php';
$edit_file = 'admin_edit_booking.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['decision'])) {
    $booking_id = (int)$_POST['booking_id'];
    $decision = $_POST['decision'] === 'approved' ? 'approved' : 'rejected';
    $remarks = trim($_POST['review_remarks'] ?? '');
    $beforeBooking = null;
    $newPaymentStatus = 'unpaid';
    try {
        $conn->begin_transaction();

        $lockContext = booking_lock_context_mysqli($conn, $booking_id);
        if (!$lockContext) {
            throw new RuntimeException('Booking not found.');
        }
        $beforeStmt = $conn->prepare("SELECT b.booking_id, b.user_id, b.resource_type, b.room_id, b.facility_id, b.booking_start, b.booking_end, b.total_price, b.booking_status, b.payment_status, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1 FOR UPDATE");
        $beforeStmt->bind_param('i', $booking_id);
        $beforeStmt->execute();
        $beforeBooking = $beforeStmt->get_result()->fetch_assoc();
        if (!$beforeBooking) {
            throw new RuntimeException('Booking not found.');
        }
        if ((string)$beforeBooking['booking_status'] !== 'pending') {
            throw new RuntimeException('Only pending booking requests can be approved or rejected from this list.');
        }

        if ($decision === 'approved') {
            $resourceId = booking_active_resource_id($beforeBooking);
            $availability = booking_validate_resource_availability_mysqli($conn, (string)$beforeBooking['resource_type'], $resourceId, (string)$beforeBooking['booking_start'], (string)$beforeBooking['booking_end'], $booking_id, true);
            if (!$availability['ok']) {
                throw new RuntimeException($availability['message']);
            }
        }

        $newPaymentStatus = booking_payment_after_status_change($decision, (string)$beforeBooking['payment_status'], (float)$beforeBooking['total_price']);
        $requestFingerprint = $decision === 'approved'
            ? booking_request_fingerprint(
                (string)$beforeBooking['resource_type'],
                booking_active_resource_id($beforeBooking),
                (string)$beforeBooking['booking_start'],
                (string)$beforeBooking['booking_end']
            )
            : null;
        $stmt = $conn->prepare("UPDATE bookings SET booking_status=?, payment_status=?, request_fingerprint=?, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=? AND booking_status='pending'");
        $stmt->bind_param('sssisi', $decision, $newPaymentStatus, $requestFingerprint, $user['user_id'], $remarks, $booking_id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new RuntimeException('Booking status changed before this action could be saved. Please refresh and try again.');
        }

        if ($decision === 'approved') {
            booking_acquire_claims_mysqli($conn, $beforeBooking, (string)$lockContext['role']);
        } else {
            booking_release_claims_mysqli($conn, $booking_id);
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        header('Location: ' . $self_file . '?error=' . urlencode($e->getMessage()));
        exit;
    }

    $notificationResult = $beforeBooking
        ? notify_booking_status_change_mysqli($conn, $beforeBooking, $decision, $newPaymentStatus)
        : null;
    $msg = $beforeBooking ? 'Booking #' . $booking_id . ' changed to ' . $decision . '. In-app notification created.' : 'Booking not found.';
    if ($notificationResult && $notificationResult['email_attempted']) {
        $msg .= $notificationResult['email_success']
            ? ' Approval email sent.'
            : ' Approval email failed; check the mail log (request ' . ($notificationResult['email_request_id'] ?: 'unknown') . ').';
    }
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
$sql="SELECT b.booking_id, b.booking_start, b.booking_end, b.purpose, b.total_price, b.booking_status, b.payment_status, b.review_remarks, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id" . ($where ? ' WHERE '.implode(' AND ', $where) : '') . " ORDER BY $order";
$stmt=$conn->prepare($sql); if($types) $stmt->bind_param($types, ...$params); $stmt->execute(); $list=$stmt->get_result();
$page_title='Admin Booking Requests'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<?php if (!empty($_GET['success'])): ?><div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800"><?= h($_GET['success']) ?></div><?php endif; ?>
<?php if (!empty($_GET['error'])): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800"><?= h($_GET['error']) ?></div><?php endif; ?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Admin Booking Requests</h1><p class="text-slate-500 mt-2">Filter by booking date, payment status and sort order.</p></div>
<form class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm mb-6 grid md:grid-cols-5 gap-4">
    <select name="status" class="input"><option value="all">All Booking Status</option><?php foreach(['pending','approved','rejected','cancelled','completed','expired','return_overdue'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ', $s)) ?></option><?php endforeach; ?></select>
    <select name="payment_status" class="input"><option value="all">All Payment Status</option><?php foreach(['unpaid','pending_verification','paid','payment_rejected','refunded'] as $p): ?><option value="<?= $p ?>" <?= $payment===$p?'selected':'' ?>><?= ucwords(str_replace('_',' ',$p)) ?></option><?php endforeach; ?></select>
    <input type="date" name="booking_date" class="input" value="<?= h($date) ?>">
    <select name="sort" class="input"><?php foreach(['newest'=>'Newest request','oldest'=>'Oldest request','date_asc'=>'Booking date ↑','date_desc'=>'Booking date ↓','price_high'=>'Price high → low','price_low'=>'Price low → high'] as $k=>$v): ?><option value="<?= $k ?>" <?= $sort===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?></select>
    <button class="btn-primary">Apply Filter</button>
</form>
<div class="bg-white rounded-xl border border-[#dcc0c2] shadow-sm overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Booking</th><th class="table-th">Requester</th><th class="table-th">Resource</th><th class="table-th">Date/Time</th><th class="table-th">Payment</th><th class="table-th">Status</th><th class="table-th">Action</th></tr></thead><tbody>
        <?php if($list->num_rows===0): ?><tr><td class="table-td text-center text-slate-500" colspan="7">No booking request found.</td></tr><?php endif; ?>
        <?php while($b=$list->fetch_assoc()): ?><tr><td class="table-td font-bold">#<?= h($b['booking_id']) ?><p class="text-xs text-slate-500"><?= h($b['purpose']) ?></p></td><td class="table-td"><?= h($b['full_name']) ?><p class="text-xs text-slate-500"><?= h($b['email']) ?></p></td><td class="table-td"><?= h($b['resource_name']) ?><p class="text-xs text-slate-500"><?= h($b['location']) ?></p></td><td class="table-td"><?= h($b['booking_start']) ?><p class="text-xs text-slate-500">to <?= h($b['booking_end']) ?></p></td><td class="table-td"><span class="badge badge-<?= h($b['payment_status']) ?>"><?= h(str_replace('_',' ',$b['payment_status'])) ?></span><p class="text-xs text-slate-500 mt-1">RM <?= number_format((float)$b['total_price'],2) ?></p></td><td class="table-td"><span class="badge badge-<?= h($b['booking_status']) ?>"><?= h($b['booking_status']) ?></span><?php if(!empty($b['review_remarks'])): ?><p class="text-xs text-slate-500 mt-1">Remarks: <?= h($b['review_remarks']) ?></p><?php endif; ?></td><td class="table-td min-w-[240px]"><a class="btn-light text-xs py-2 mb-2" href="<?= h($edit_file) ?>?id=<?= h($b['booking_id']) ?>">View Details</a><form method="post" class="space-y-2"><input type="hidden" name="booking_id" value="<?= h($b['booking_id']) ?>"><input name="review_remarks" class="input text-xs" placeholder="New review remarks"><div class="flex gap-2"><button type="submit" name="decision" value="approved" data-payment-status="<?= h($b['payment_status']) ?>" onclick="return confirmApprovePaymentStatus(this.dataset.paymentStatus)" class="bg-green-700 text-white px-3 py-2 rounded font-bold text-xs">Approve</button><button type="submit" name="decision" value="rejected" class="bg-red-700 text-white px-3 py-2 rounded font-bold text-xs">Reject</button></div></form></td></tr><?php endwhile; ?></tbody></table></div>
<script>
function confirmApprovePaymentStatus(paymentStatus) {
    if (paymentStatus === 'pending_verification') {
        return confirm('Payment status is Pending Verification. Approving this booking will notify the user while payment verification is still pending. Continue?');
    }
    if (paymentStatus === 'unpaid') {
        return confirm('Payment status is Unpaid. Approving this booking will notify the user to complete payment. Continue?');
    }
    return true;
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

