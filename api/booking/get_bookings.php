<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../../config/db.php';

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {

    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);

    exit;
}

$sql = "SELECT * FROM bookings
WHERE user_id = ?
ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([intval($user_id)]);

$result = $stmt->fetchAll();

?>
