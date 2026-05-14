<?php
$facility_id = $_POST['facility_id'] ?? '';
$booking_date = $_POST['booking_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$status = "Pending";
$total_cost = $_POST['total_cost'] ?? 0;
$comments = $_POST['comments'] ?? '';

if (
    empty($user_id) ||
    empty($facility_id) ||
    empty($booking_date) ||
    empty($start_time) ||
    empty($end_time)
) {

    echo json_encode([
        "success" => false,
        "message" => "All required fields must be filled"
    ]);

    exit;
}

$sql = "INSERT INTO bookings
(user_id, facility_id, booking_date, start_time, end_time, status, total_cost, comments)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iissssds",
    $user_id,
    $facility_id,
    $booking_date,
    $start_time,
    $end_time,
    $status,
    $total_cost,
    $comments
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Booking created successfully"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to create booking"
    ]);
}

$stmt->close();
$conn->close();

?>
