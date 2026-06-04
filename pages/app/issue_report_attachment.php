<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/issue_report_attachment.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
