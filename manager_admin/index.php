<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['admin', 'facility_manager']);
if (($user['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
header('Location: facility_manager_dashboard.php');
exit;
