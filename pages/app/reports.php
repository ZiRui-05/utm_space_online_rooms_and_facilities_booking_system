<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/reports.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
