<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../../manager_admin/admin_verify_cards.php' . ($query !== '' ? '?' . $query : ''));
exit;
?>
