<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/logout.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
