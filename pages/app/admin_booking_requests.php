<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_booking_requests.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
