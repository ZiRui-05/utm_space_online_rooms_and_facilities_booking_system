<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$conn = new mysqli("localhost", "root", "", "unireserve");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}

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
