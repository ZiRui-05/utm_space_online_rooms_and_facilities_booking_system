<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['facility_manager']);
$id = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
if ($id <= 0) { header('Location: manager_booking_requests.php?error=' . urlencode('Missing booking ID')); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['booking_status'];
    $payment = $_POST['payment_status'];
    $start = $_POST['booking_start'];
    $end = $_POST['booking_end'];
    $purpose = trim($_POST['purpose']);
    $remarks = trim($_POST['review_remarks']);
    $total = (float)$_POST['total_price'];
    if (strtotime($end) <= strtotime($start)) { header('Location: manager_edit_booking.php?id=' . $id . '&error=' . urlencode('End date/time must be after start date/time')); exit; }
    $stmt = $conn->prepare('UPDATE bookings SET booking_status=?, payment_status=?, booking_start=?, booking_end=?, purpose=?, total_price=?, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=?');
    $stmt->bind_param('sssssdisi', $status, $payment, $start, $end, $purpose, $total, $user['user_id'], $remarks, $id);
    $stmt->execute();
    header('Location: manager_booking_requests.php?success=' . urlencode('Booking updated successfully')); exit;
}
$stmt=$conn->prepare("SELECT b.*, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1");
$stmt->bind_param('i',$id); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc();
if(!$booking) { header('Location: manager_booking_requests.php?error=' . urlencode('Booking not found')); exit; }
$page_title='Facility Manager Edit Booking'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Facility Manager Edit Booking</h1><p class="text-slate-500 mt-2">Edit the booking even after it has already been approved or rejected.</p></div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm">
        <h2 class="text-xl font-black text-[#36000f] mb-4">Booking Summary</h2>
        <p class="text-sm text-slate-500">Booking ID</p><p class="font-bold mb-3">#<?= h($booking['booking_id']) ?></p>
        <p class="text-sm text-slate-500">Requester</p><p class="font-bold mb-3"><?= h($booking['full_name']) ?><br><span class="text-sm font-normal"><?= h($booking['email']) ?></span></p>
        <p class="text-sm text-slate-500">Resource</p><p class="font-bold mb-3"><?= h($booking['resource_name']) ?><br><span class="text-sm font-normal"><?= h($booking['location']) ?></span></p>
        <a class="btn-light mt-3" href="manager_booking_requests.php">Back to Booking Requests</a>
    </div>
    <form method="post" class="lg:col-span-2 bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4">
        <input type="hidden" name="booking_id" value="<?= h($booking['booking_id']) ?>">
        <div class="grid md:grid-cols-2 gap-4">
            <div><label class="text-sm font-bold text-slate-600">Booking Status</label><select class="input mt-1" name="booking_status"><?php foreach(['pending','approved','rejected','cancelled','completed','expired'] as $s): ?><option value="<?= $s ?>" <?= $booking['booking_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-bold text-slate-600">Payment Status</label><select class="input mt-1" name="payment_status"><?php foreach(['unpaid','paid','refunded'] as $s): ?><option value="<?= $s ?>" <?= $booking['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-bold text-slate-600">Start Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_start" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_start'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">End Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_end" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_end'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">Total Price (RM)</label><input class="input mt-1" type="number" step="0.01" name="total_price" value="<?= h($booking['total_price']) ?>"></div>
        </div>
        <div><label class="text-sm font-bold text-slate-600">Purpose</label><textarea class="input mt-1" name="purpose" rows="3"><?= h($booking['purpose']) ?></textarea></div>
        <div><label class="text-sm font-bold text-slate-600">Review Remarks</label><textarea class="input mt-1" name="review_remarks" rows="3"><?= h($booking['review_remarks']) ?></textarea></div>
        <button class="btn-primary">Save Booking Changes</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
