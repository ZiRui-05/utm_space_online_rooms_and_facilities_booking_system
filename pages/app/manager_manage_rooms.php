<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_manage_rooms.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
