<?php
/**
 * Logout script for Rengganis Net
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

initAppSession();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

initAppSession();
$_SESSION['flash_info'] = 'Anda telah berhasil keluar dari sistem.';
session_write_close();

header('Location: login.php');
exit;
