<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manage_users.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
