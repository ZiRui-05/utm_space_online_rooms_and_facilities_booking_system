<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user']['user_id'];

$sql = "SELECT b.booking_id,
               b.resource_type,
               b.booking_start,
               b.booking_end,
               b.booking_status,
               b.total_price AS cost,
               b.created_at,
               COALESCE(r.room_name, f.facility_name) AS resource_name
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN facilities f ON b.facility_id = f.facility_id
        WHERE b.user_id = ?
        ORDER BY b.booking_start DESC, b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    'success' => true,
    'bookings' => $bookings,
]);
