<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_role(['admin', 'facility_manager']);
$userId = (int)($_GET['id'] ?? 0);
$kind = (string)($_GET['kind'] ?? '');

$imageColumns = [
    'profile' => ['profile_image_base64', 'profile_image_mime', 'profile'],
    'utm_front' => ['utm_card_base64', 'utm_card_mime', 'utm-card-front'],
    'utm_back' => ['utm_card_back_base64', 'utm_card_back_mime', 'utm-card-back'],
];

if ($userId <= 0 || !isset($imageColumns[$kind])) {
    http_response_code(404);
    exit('Image not found.');
}

[$dataColumn, $mimeColumn, $filenamePrefix] = $imageColumns[$kind];
$stmt = $conn->prepare("SELECT user_id, $dataColumn AS image_base64, $mimeColumn AS image_mime FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['image_base64'])) {
    http_response_code(404);
    exit('Image not found.');
}

$binary = base64_decode((string)$row['image_base64'], true);
if ($binary === false) {
    http_response_code(404);
    exit('Image not found.');
}

$mime = (string)($row['image_mime'] ?: 'image/jpeg');
if (!str_starts_with($mime, 'image/')) {
    $mime = 'image/jpeg';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($binary));
header('Cache-Control: private, max-age=3600');
header('Content-Disposition: inline; filename="' . $filenamePrefix . '-' . $userId . '"');
echo $binary;
exit;
?>
