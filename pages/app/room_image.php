<?php
require_once __DIR__ . '/../../config/db.php';

$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($roomId <= 0 || !isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(404);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT room_image_base64, room_image_mime FROM rooms WHERE room_id = :id LIMIT 1');
    $stmt->execute(['id' => $roomId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['room_image_base64']) || empty($row['room_image_mime'])) {
        http_response_code(404);
        exit;
    }

    $binary = base64_decode((string)$row['room_image_base64'], true);
    if ($binary === false) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $row['room_image_mime']);
    header('Cache-Control: public, max-age=86400');
    echo $binary;
} catch (Throwable $e) {
    http_response_code(500);
}
