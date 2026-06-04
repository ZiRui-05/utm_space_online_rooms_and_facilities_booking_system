<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_profile.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
