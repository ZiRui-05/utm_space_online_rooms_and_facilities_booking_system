<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/booking_expiry.php';
require_once __DIR__ . '/../includes/booking_validation.php';
require_once __DIR__ . '/../includes/booking_constraints.php';
$user = require_role(['admin']);
$id = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
if ($id <= 0) { header('Location: admin_booking_requests.php?error=' . urlencode('Missing booking ID')); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowedBookingStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed', 'expired', 'return_overdue'];
    $allowedPaymentStatuses = ['unpaid', 'pending_verification', 'paid', 'payment_rejected', 'refunded'];
    $quickStatus = trim((string)($_POST['quick_status'] ?? ''));
    $postedStatus = $quickStatus !== '' ? $quickStatus : trim((string)($_POST['booking_status'] ?? ''));
    $postedPayment = trim((string)($_POST['payment_status'] ?? ''));
    $status = in_array($postedStatus, $allowedBookingStatuses, true) ? $postedStatus : '';
    $payment = in_array($postedPayment, $allowedPaymentStatuses, true) ? $postedPayment : '';

    $startInput = trim((string)($_POST['booking_start'] ?? ''));
    $endInput = trim((string)($_POST['booking_end'] ?? ''));
    $purpose = trim((string)($_POST['purpose'] ?? ''));
    $remarks = trim((string)($_POST['review_remarks'] ?? ''));
    $total = (float)($_POST['total_price'] ?? 0);

    if ($startInput === '' || $endInput === '' || strtotime($startInput) === false || strtotime($endInput) === false) {
        header('Location: admin_edit_booking.php?id=' . $id . '&error=' . urlencode('Please enter a valid start and end date/time.')); exit;
    }
    if (strtotime($endInput) <= strtotime($startInput)) {
        header('Location: admin_edit_booking.php?id=' . $id . '&error=' . urlencode('End date/time must be after start date/time')); exit;
    }
    if ($total < 0) {
        header('Location: admin_edit_booking.php?id=' . $id . '&error=' . urlencode('Total price cannot be negative.')); exit;
    }

    $start = date('Y-m-d H:i:s', strtotime($startInput));
    $end = date('Y-m-d H:i:s', strtotime($endInput));

    try {
        $conn->begin_transaction();

        $lockContext = booking_lock_context_mysqli($conn, $id);
        if (!$lockContext) {
            throw new RuntimeException('Booking not found');
        }
        $beforeStmt = $conn->prepare("SELECT b.booking_id, b.user_id, b.resource_type, b.room_id, b.facility_id, b.booking_start, b.booking_end, b.total_price, b.booking_status, b.payment_status, u.full_name, u.email, COALESCE(r.room_name, f.facility_name) resource_name FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1 FOR UPDATE");
        $beforeStmt->bind_param('i', $id);
        $beforeStmt->execute();
        $beforeBooking = $beforeStmt->get_result()->fetch_assoc();
        if (!$beforeBooking) {
            throw new RuntimeException('Booking not found');
        }
        if ($status === '') {
            $status = (string)$beforeBooking['booking_status'];
        }
        if ($payment === '') {
            $payment = (string)$beforeBooking['payment_status'];
        }

        $statusChanged = $status !== (string)$beforeBooking['booking_status'];
        $timeChanged = strtotime((string)$beforeBooking['booking_start']) !== strtotime($start)
            || strtotime((string)$beforeBooking['booking_end']) !== strtotime($end);

        if (in_array($status, ['pending', 'approved'], true) && ($statusChanged || $timeChanged)) {
            $resourceId = booking_active_resource_id($beforeBooking);
            $availability = booking_validate_resource_availability_mysqli($conn, (string)$beforeBooking['resource_type'], $resourceId, $start, $end, $id, true);
            if (!$availability['ok']) {
                throw new RuntimeException($availability['message']);
            }
        }

        $payment = booking_payment_after_status_change($status, $payment, $total);
        $requestFingerprint = booking_is_active_status($status)
            ? booking_request_fingerprint(
                (string)$beforeBooking['resource_type'],
                booking_active_resource_id($beforeBooking),
                $start,
                $end
            )
            : null;

        $stmt = $conn->prepare('UPDATE bookings SET booking_status=?, payment_status=?, request_fingerprint=?, booking_start=?, booking_end=?, purpose=?, total_price=?, reviewed_by=?, reviewed_at=NOW(), review_remarks=? WHERE booking_id=? LIMIT 1');
        $stmt->bind_param('ssssssdisi', $status, $payment, $requestFingerprint, $start, $end, $purpose, $total, $user['user_id'], $remarks, $id);
        $stmt->execute();

        if (booking_is_active_status($status)) {
            $claimBooking = $beforeBooking;
            $claimBooking['booking_start'] = $start;
            $claimBooking['booking_end'] = $end;
            booking_acquire_claims_mysqli($conn, $claimBooking, (string)$lockContext['role']);
        } else {
            booking_release_claims_mysqli($conn, $id);
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        header('Location: admin_edit_booking.php?id=' . $id . '&error=' . urlencode($e->getMessage())); exit;
    }

    $notificationResult = notify_booking_status_change_mysqli($conn, $beforeBooking, $status, $payment);
    suspend_users_with_missed_payments_mysqli($conn);
    $message = 'Booking #' . $id . ' updated to ' . $status . '.';
    if ($notificationResult['email_attempted']) {
        $message .= $notificationResult['email_success']
            ? ' Approval email sent.'
            : ' Approval email failed; check the mail log (request ' . ($notificationResult['email_request_id'] ?: 'unknown') . ').';
    }
    header('Location: admin_booking_requests.php?success=' . urlencode($message)); exit;
}
$stmt=$conn->prepare("SELECT b.booking_id, b.user_id, b.booking_start, b.booking_end, b.purpose, b.total_price, b.booking_status, b.payment_status, b.review_remarks,
    u.full_name, u.email, u.phone_number, u.utm_id, u.role,
    CASE WHEN u.profile_image_base64 IS NULL OR u.profile_image_base64 = '' THEN 0 ELSE 1 END has_profile_image,
    CASE WHEN u.utm_card_base64 IS NULL OR u.utm_card_base64 = '' THEN 0 ELSE 1 END has_utm_card_front,
    CASE WHEN u.utm_card_back_base64 IS NULL OR u.utm_card_back_base64 = '' THEN 0 ELSE 1 END has_utm_card_back,
    COALESCE(r.room_name, f.facility_name) resource_name, COALESCE(r.location, f.location) location
    FROM bookings b JOIN users u ON u.user_id=b.user_id LEFT JOIN rooms r ON r.room_id=b.room_id LEFT JOIN facilities f ON f.facility_id=b.facility_id WHERE b.booking_id=? LIMIT 1");
$stmt->bind_param('i',$id); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc();
if(!$booking) { header('Location: admin_booking_requests.php?error=' . urlencode('Booking not found')); exit; }
$placeholder = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
$profile = (int)$booking['has_profile_image'] === 1 ? 'user_image.php?id=' . (int)$booking['user_id'] . '&kind=profile' : 'https://ui-avatars.com/api/?name=' . urlencode($booking['full_name'] ?? 'User') . '&background=5c001f&color=fff';
$page_title='Admin Edit Booking'; $active_page='bookings'; include __DIR__ . '/includes/header.php';
?>
<div class="mb-8"><h1 class="text-4xl font-black text-[#36000f]">Booking Request Details</h1><p class="text-slate-500 mt-2">Review requester and booking details before approving or rejecting.</p></div>
<?php if (!empty($_GET['success'])): ?><div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800"><?= h($_GET['success']) ?></div><?php endif; ?>
<?php if (!empty($_GET['error'])): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800"><?= h($_GET['error']) ?></div><?php endif; ?>
<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-xl border border-[#dcc0c2] p-5 shadow-sm">
        <div class="flex justify-between items-start gap-4 mb-4">
            <h2 class="text-xl font-black text-[#36000f]">Requester Information</h2>
            <a class="btn-light" href="admin_booking_requests.php">Back to Booking Requests</a>
        </div>
        <div class="grid lg:grid-cols-4 gap-4">
            <div class="bg-[#fff7f8] border border-[#dcc0c2] rounded-lg p-3">
                <p class="text-xs uppercase font-bold text-slate-500 mb-2">Profile Picture</p>
                <img src="<?= $placeholder ?>" data-async-src="<?= h($profile) ?>" loading="lazy" decoding="async" class="w-full h-36 object-cover rounded-lg border" alt="Profile picture">
            </div>
            <div class="lg:col-span-2 grid sm:grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs uppercase font-bold text-slate-500">Name</p><p class="font-bold"><?= h($booking['full_name']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Role</p><p><?= h(ucwords(str_replace('_',' ', $booking['role'] ?? ''))) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">UTM ID</p><p><?= h($booking['utm_id']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Booking ID</p><p class="font-bold">#<?= h($booking['booking_id']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Email</p><p><?= h($booking['email']) ?></p></div>
                <div><p class="text-xs uppercase font-bold text-slate-500">Phone</p><p><?= h($booking['phone_number']) ?></p></div>
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
    </div>
    <form id="booking-edit-form" method="post" class="bg-white rounded-xl border border-[#dcc0c2] p-6 shadow-sm space-y-4">
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
            <div><label class="text-sm font-bold text-slate-600">Booking Status</label><select class="input mt-1" name="booking_status"><?php foreach(['pending','approved','rejected','cancelled','completed','expired','return_overdue'] as $s): ?><option value="<?= $s ?>" <?= $booking['booking_status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ', $s)) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-bold text-slate-600">Payment Status</label><select class="input mt-1" name="payment_status"><?php foreach(['unpaid','pending_verification','paid','payment_rejected','refunded'] as $s): ?><option value="<?= $s ?>" <?= $booking['payment_status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ', $s)) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-bold text-slate-600">Start Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_start" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_start'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">End Date/Time</label><input class="input mt-1" type="datetime-local" name="booking_end" value="<?= date('Y-m-d\TH:i', strtotime($booking['booking_end'])) ?>" required></div>
            <div><label class="text-sm font-bold text-slate-600">Total Price (RM)</label><input class="input mt-1" type="number" min="0" step="0.01" name="total_price" value="<?= h($booking['total_price']) ?>"></div>
        </div>
        <div><label class="text-sm font-bold text-slate-600">Remarks / Purpose</label><textarea class="input mt-1" name="purpose" rows="3"><?= h($booking['purpose']) ?></textarea></div>
        <div><label class="text-sm font-bold text-slate-600">Review Remarks</label><textarea class="input mt-1" name="review_remarks" rows="3"><?= h($booking['review_remarks']) ?></textarea></div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" name="quick_status" value="approved" data-payment-status="<?= h($booking['payment_status']) ?>" onclick="return confirmApprovePaymentStatus(this.dataset.paymentStatus)" class="bg-green-700 text-white px-4 py-2 rounded-lg font-bold">Approve Booking</button>
            <button type="submit" name="quick_status" value="rejected" class="bg-red-700 text-white px-4 py-2 rounded-lg font-bold">Reject Booking</button>
            <button type="submit" class="btn-primary">Save Booking Changes</button>
        </div>
        <script>
        function setBookingStatusAndSubmit(status) {
            const select = document.querySelector('select[name="booking_status"]');
            if (select) select.value = status;
            const form = document.getElementById('booking-edit-form');
            if (form) form.submit();
        }

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
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
