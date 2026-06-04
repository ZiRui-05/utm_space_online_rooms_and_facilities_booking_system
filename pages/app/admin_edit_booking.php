<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_edit_booking.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
