<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manage_rooms.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
