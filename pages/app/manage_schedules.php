<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manage_schedules.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
