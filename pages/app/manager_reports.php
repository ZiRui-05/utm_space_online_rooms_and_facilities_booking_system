<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_reports.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
