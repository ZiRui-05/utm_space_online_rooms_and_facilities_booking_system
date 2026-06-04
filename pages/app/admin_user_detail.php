<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_user_detail.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
