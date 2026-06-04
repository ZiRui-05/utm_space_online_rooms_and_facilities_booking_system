<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_dashboard.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
