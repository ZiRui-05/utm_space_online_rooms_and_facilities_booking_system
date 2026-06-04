<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_role(['admin', 'facility_manager']);
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    http_response_code(404);
    exit('Attachment not found.');
}

$stmt = $conn->prepare('SELECT booking_id, payment_proof_base64, payment_proof_mime FROM bookings WHERE booking_id = ? LIMIT 1');
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['payment_proof_base64']) || empty($row['payment_proof_mime'])) {
    http_response_code(404);
    exit('Attachment not found.');
}

$binary = base64_decode((string)$row['payment_proof_base64'], true);
if ($binary === false) {
    http_response_code(404);
    exit('Attachment not found.');
}

$mime = (string)$row['payment_proof_mime'];
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($binary));
header('Cache-Control: private, max-age=1800');
header('Content-Disposition: inline; filename="payment-proof-' . $bookingId . '"');
echo $binary;
exit;
?>
