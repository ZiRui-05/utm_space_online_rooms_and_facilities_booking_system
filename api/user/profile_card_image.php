<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user']['user_id'];
$kind = $_GET['kind'] ?? 'front';

if ($kind === 'back') {
    $select = 'utm_card_back_base64 AS image_base64, utm_card_back_mime AS image_mime';
} else {
    $select = 'utm_card_base64 AS image_base64, utm_card_mime AS image_mime';
}

$stmt = $pdo->prepare("SELECT {$select} FROM users WHERE user_id = :user_id LIMIT 1");
$stmt->execute(['user_id' => $userId]);
$row = $stmt->fetch();

if (!$row || empty($row['image_base64']) || empty($row['image_mime'])) {
    http_response_code(404);
    exit('No UTM card image found');
}

$binary = base64_decode((string)$row['image_base64'], true);
if ($binary === false) {
    http_response_code(500);
    exit('Invalid stored image');
}

header('Content-Type: ' . $row['image_mime']);
header('Cache-Control: private, max-age=300');
echo $binary;
