<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['admin']);
header('Location: admin_dashboard.php?error=' . urlencode('Room and facility inventory is managed by Facility Manager only.'));
exit;
?>
