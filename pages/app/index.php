<?php
require_once __DIR__ . '/includes/auth.php';
$user = current_user();
if ($user) {
    redirect_by_role($user);
}
header('Location: ../auth/login.html');
exit;
?>
