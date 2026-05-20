<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin','facility_manager']);
$prefix = $user['role'] === 'admin' ? 'admin' : 'manager';
header('Location: ' . $prefix . '_manage_users.php');
exit;
?>
