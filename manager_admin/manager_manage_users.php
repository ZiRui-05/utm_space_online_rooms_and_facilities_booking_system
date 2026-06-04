<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['facility_manager']);
header('Location: facility_manager_dashboard.php?error=' . urlencode('Manager account management is disabled'));
exit;
