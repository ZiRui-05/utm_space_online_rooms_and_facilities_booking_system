<?php
require_once __DIR__ . '/../../config/db.php';

$facilityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($facilityId <= 0 || !isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(404);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT facility_image_base64, facility_image_mime FROM facilities WHERE facility_id = :id LIMIT 1');
    $stmt->execute(['id' => $facilityId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['facility_image_base64']) || empty($row['facility_image_mime'])) {
        http_response_code(404);
        exit;
    }

    $binary = base64_decode((string)$row['facility_image_base64'], true);
    if ($binary === false) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $row['facility_image_mime']);
    header('Cache-Control: public, max-age=86400');
    echo $binary;
} catch (Throwable $e) {
    http_response_code(500);
}
