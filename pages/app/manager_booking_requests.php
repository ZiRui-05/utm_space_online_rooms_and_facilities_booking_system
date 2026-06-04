<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_booking_requests.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
