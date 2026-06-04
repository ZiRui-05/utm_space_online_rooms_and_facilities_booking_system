<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manage_facilities.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
