<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_issue_reports.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
