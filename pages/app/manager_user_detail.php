<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/manager_user_detail.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
