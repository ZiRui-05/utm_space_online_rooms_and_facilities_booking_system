<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_manage_rooms.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
