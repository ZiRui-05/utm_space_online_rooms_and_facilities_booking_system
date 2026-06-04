<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/facility_manager_dashboard.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
