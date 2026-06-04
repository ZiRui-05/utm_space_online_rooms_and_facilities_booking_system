<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_manage_users.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
