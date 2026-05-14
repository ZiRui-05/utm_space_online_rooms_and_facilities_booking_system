<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli("localhost", "root", "", "unireserve");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}

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

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>
