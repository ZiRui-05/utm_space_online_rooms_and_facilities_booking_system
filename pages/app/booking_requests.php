<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/booking_requests.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
