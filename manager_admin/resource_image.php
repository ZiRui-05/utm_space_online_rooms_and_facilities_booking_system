<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_role(['admin', 'facility_manager']);
$type = (string)($_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$resources = [
    'room' => ['rooms', 'room_id', 'room_image_base64', 'room_image_mime', 'room'],
    'facility' => ['facilities', 'facility_id', 'facility_image_base64', 'facility_image_mime', 'facility'],
];

if ($id <= 0 || !isset($resources[$type])) {
    http_response_code(404);
    exit('Image not found.');
}

[$table, $idColumn, $dataColumn, $mimeColumn, $filenamePrefix] = $resources[$type];
$stmt = $conn->prepare("SELECT $dataColumn AS image_base64, $mimeColumn AS image_mime FROM $table WHERE $idColumn = ? LIMIT 1");
$stmt->bind_param('i', $id);
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
header('Content-Disposition: inline; filename="' . $filenamePrefix . '-' . $id . '"');
echo $binary;
exit;
?>
