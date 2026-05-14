<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);

    exit;
}

$id = $_POST['id'] ?? '';

if (empty($id)) {

    echo json_encode([
        "success" => false,
        "message" => "Booking ID required"
    ]);

    exit;
}
?>
