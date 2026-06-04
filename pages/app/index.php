<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/index.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
