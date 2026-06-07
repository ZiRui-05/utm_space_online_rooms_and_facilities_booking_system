<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
$user = require_role(['facility_manager', 'admin']);
$id = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
if ($id <= 0) { header('Location: manager_booking_requests.php?error=' . urlencode('Missing booking ID')); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beforeStmt = $conn->prepare("SELECT b.booking_id, b.user_id, b.booking_status, b.payment_status, COALESCE(r.room_name, f.facility_name) resource_name FROM bookings b LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1");
    $beforeStmt->bind_param('i', $id);
    $beforeStmt->execute();
    $beforeBooking = $beforeStmt->get_result()->fetch_assoc();
    if (!$beforeBooking) { header('Location: manager_booking_requests.php?error=' . urlencode('Booking not found')); exit; }

    $status = $_POST['booking_status'];
    $payment = $_POST['payment_status'];
    $start = $_POST['booking_start'];
    $end = $_POST['booking_end'];
    $purpose = trim($_POST['purpose']);
    $remarks = trim($_POST['review_remarks']);
    $total = (float)$_POST['total_price'];
    if (strtotime($end) <= strtotime($start)) { header('Location: manager_edit_booking.php?id=' . $id . '&error=' . urlencode('End date/time must be after start date/time')); exit; }
    if (in_array($status, ['rejected', 'expired'], true) && in_array($payment, ['paid', 'pending_verification'], true)) {
        $payment = 'refunded';
    }
    $stmt = $conn->prepare('UPDATE bookings SET booking_status=?, payment_status=?, booking_start=?, booking_end=?, purpose=?, total_price=?, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=?');
    $stmt->bind_param('sssssdisi', $status, $payment, $start, $end, $purpose, $total, $user['user_id'], $remarks, $id);
    $stmt->execute();
    notify_booking_status_change_mysqli($conn, $beforeBooking, $status, $payment);
    suspend_users_with_missed_payments_mysqli($conn);
    header('Location: manager_booking_requests.php?success=' . urlencode('Booking updated successfully')); exit;
}
$stmt=$conn->prepare("SELECT b.booking_id, b.user_id, b.booking_start, b.booking_end, b.purpose, b.total_price, b.booking_status, b.payment_status, b.payment_proof_mime, b.review_remarks,
    CASE WHEN b.payment_proof_base64 IS NULL OR b.payment_proof_base64 = '' THEN 0 ELSE 1 END has_payment_attachment,
    u.full_name, u.email, u.phone_number, u.utm_id, u.role,
    CASE WHEN u.profile_image_base64 IS NULL OR u.profile_image_base64 = '' THEN 0 ELSE 1 END has_profile_image,
    CASE WHEN u.utm_card_base64 IS NULL OR u.utm_card_base64 = '' THEN 0 ELSE 1 END has_utm_card_front,
    CASE WHEN u.utm_card_back_base64 IS NULL OR u.utm_card_back_base64 = '' THEN 0 ELSE 1 END has_utm_card_back,
    COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location
    FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1");
$stmt->bind_param('i',$id); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc();
if(!$booking) { header('Location: manager_booking_requests.php?error=' . urlencode('Booking not found')); exit; }
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
$profile = (int)$booking['has_profile_image'] === 1 ? 'user_image.php?id=' . (int)$booking['user_id'] . '&kind=profile' : 'https://ui-avatars.com/api/?name=' . urlencode($booking['full_name'] ?? 'User') . '&background=5c001f&color=fff';
$hasPaymentAttachment = (int)($booking['has_payment_attachment'] ?? 0) === 1 && !empty($booking['payment_proof_mime']);
$paymentAttachmentMime = (string)($booking['payment_proof_mime'] ?? '');
$paymentAttachmentSrc = $hasPaymentAttachment ? 'booking_attachment.php?id=' . (int)$booking['booking_id'] : '';
$isPaymentAttachmentImage = str_starts_with($paymentAttachmentMime, 'image/');
$isPaymentAttachmentPdf = $paymentAttachmentMime === 'application/pdf';
$page_title='Facility Manager Edit Booking'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Booking Request Details</h1><p class="text-slate-500 mt-2">Review requester and booking details before approving or rejecting.</p></div>
<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm">
        <div class="flex justify-between items-start gap-4 mb-4">
            <h2 class="text-xl font-black text-[#36000f]">Requester Information</h2>
            <a class="btn-light" href="manager_booking_requests.php">Back to Booking Requests</a>
        </div>
        <div class="grid lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-[#fff7f8] border border-[#dcc0c2] rounded-lg p-3">
                    <p class="text-xs uppercase font-bold text-slate-500 mb-2">Profile Picture</p>
                    <img src="<?= $placeholder ?>" data-async-src="<?= h($profile) ?>" loading="lazy" decoding="async" class="w-full h-36 object-contain rounded-lg border bg-white" alt="Profile picture">
                </div>
                <div class="bg-[#fff7f8] border border-[#dcc0c2] rounded-lg p-3">
                    <p class="text-xs uppercase font-bold text-slate-500 mb-2">UTM Card</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs uppercase font-bold text-slate-500 mb-1">Front</p>
                            <?php if((int)$booking['has_utm_card_front'] === 1): ?>
                                <img src="<?= $placeholder ?>" data-async-src="user_image.php?id=<?= h($booking['user_id']) ?>&kind=utm_front" loading="lazy" decoding="async" class="w-full h-36 object-contain rounded-lg border bg-white" alt="UTM card front">
                            <?php else: ?>
                                <p class="text-slate-500 text-sm">No front card uploaded.</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-xs uppercase font-bold text-slate-500 mb-1">Back</p>
                            <?php if((int)$booking['has_utm_card_back'] === 1): ?>
                                <img src="<?= $placeholder ?>" data-async-src="user_image.php?id=<?= h($booking['user_id']) ?>&kind=utm_back" loading="lazy" decoding="async" class="w-full h-36 object-contain rounded-lg border bg-white" alt="UTM card back">
                            <?php else: ?>
                                <p class="text-slate-500 text-sm">No back card uploaded.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2 grid sm:grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs uppercase font-bold text-slate-500">Name</p><p class="font-bold"><?= h($booking['full_name']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Role</p><p><?= h(ucwords(str_replace('_',' ', $booking['role'] ?? ''))) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">UTM ID</p><p><?= h($booking['utm_id']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Booking ID</p><p class="font-bold">#<?= h($booking['booking_id']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Email</p><p><?= h($booking['email']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Phone</p><p><?= h($booking['phone_number']) ?></p></div>
            </div>
        </div>
    </div>
    <form method="post" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4">
        <input type="hidden" name="booking_id" value="<?= h($booking['booking_id']) ?>">
        <h2 class="text-xl font-black text-[#36000f]">Booking Information</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div><p class="text-xs uppercase font-bold text-slate-500">Room / Facility</p><p class="font-bold"><?= h($booking['resource_name']) ?></p></div>
            <div><p class="text-xs uppercase font-bold text-slate-500">Location</p><p><?= h($booking['location']) ?></p></div>
            <div><p class="text-xs uppercase font-bold text-slate-500">Date</p><p><?= h(date('Y-m-d', strtotime($booking['booking_start']))) ?></p></div>
            <div><p class="text-xs uppercase font-bold text-slate-500">Start Time</p><p><?= h(date('H:i', strtotime($booking['booking_start']))) ?></p></div>
            <div><p class="text-xs uppercase font-bold text-slate-500">End Time</p><p><?= h(date('H:i', strtotime($booking['booking_end']))) ?></p></div>
            <div><p class="text-xs uppercase font-bold text-slate-500">Cost (RM)</p><p class="font-bold"><?= number_format((float)$booking['total_price'], 2) ?></p></div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div><label class="text-sm font-bold text-slate-600">Booking Status</label><select class="input mt-1" name="booking_status"><?php foreach(['pending','approved','rejected','cancelled','completed','expired'] as $s): ?><option value="<?= $s ?>" <?= $booking['booking_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-bold text-slate-600">Total Price (RM)</label><input class="input mt-1" type="number" step="0.01" name="total_price" value="<?= h($booking['total_price']) ?>"></div>
            <div><label class="text-sm font-bold text-slate-600">Start Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_start" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_start'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">End Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_end" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_end'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">Payment Status</label><select class="input mt-1" name="payment_status"><?php foreach(['unpaid','pending_verification','paid','payment_rejected','refunded'] as $s): ?><option value="<?= $s ?>" <?= $booking['payment_status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ', $s)) ?></option><?php endforeach; ?></select><button type="button" class="btn-light mt-2 <?= $hasPaymentAttachment ? '' : 'opacity-60 cursor-not-allowed' ?>" onclick="openPaymentAttachment()">View Payment Attachment</button></div>
        </div>
        <div><label class="text-sm font-bold text-slate-600">Remarks / Purpose</label><textarea class="input mt-1" name="purpose" rows="3"><?= h($booking['purpose']) ?></textarea></div>
        <div><label class="text-sm font-bold text-slate-600">Review Remarks</label><textarea class="input mt-1" name="review_remarks" rows="3"><?= h($booking['review_remarks']) ?></textarea></div>
        <button class="btn-primary">Save Booking Changes</button>
    </form>
</div>
<div id="payment-attachment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b p-4">
            <div>
                <h2 class="text-lg font-black text-[#36000f]">Payment Attachment</h2>
                <p class="text-xs text-slate-500">Booking #<?= h($booking['booking_id']) ?></p>
            </div>
            <button type="button" class="btn-light" onclick="closePaymentAttachment()">Close</button>
        </div>
        <div class="p-4 bg-slate-50">
            <?php if ($hasPaymentAttachment && $isPaymentAttachmentImage): ?>
                <img src="<?= $placeholder ?>" data-async-src="<?= h($paymentAttachmentSrc) ?>" loading="lazy" decoding="async" class="mx-auto max-h-[70vh] w-auto max-w-full object-contain rounded-lg border bg-white" alt="Payment receipt attachment">
            <?php elseif ($hasPaymentAttachment && $isPaymentAttachmentPdf): ?>
                <iframe data-async-src="<?= h($paymentAttachmentSrc) ?>" class="h-[70vh] w-full rounded-lg border bg-white" title="Payment receipt attachment"></iframe>
            <?php else: ?>
                <div class="rounded-lg border bg-white p-8 text-center text-slate-500">No payment attachment uploaded.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function openPaymentAttachment() {
    <?php if (!$hasPaymentAttachment): ?>
    alert('No payment attachment uploaded.');
    return;
    <?php endif; ?>
    document.getElementById('payment-attachment-modal').classList.remove('hidden');
    document.getElementById('payment-attachment-modal').classList.add('flex');
    const modal = document.getElementById('payment-attachment-modal');
    const deferredFrame = modal.querySelector('iframe[data-async-src]:not([src])');
    if (deferredFrame) deferredFrame.src = deferredFrame.dataset.asyncSrc;
    if (window.chunkedImageLoader) window.chunkedImageLoader.enqueue(modal);
}

function closePaymentAttachment() {
    document.getElementById('payment-attachment-modal').classList.add('hidden');
    document.getElementById('payment-attachment-modal').classList.remove('flex');
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
