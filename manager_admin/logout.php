<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $paths = array_unique([$params['path'] ?: '/', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')]);
    foreach ($paths as $path) {
        if (!$path || $path === '.') {
            $path = '/';
        }
        setcookie(session_name(), '', time() - 42000, $path, $params['domain'] ?? '', !empty($params['secure']), !empty($params['httponly']));
    }
}

session_destroy();
header('Location: ../pages/auth/login.php');
exit;
