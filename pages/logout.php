<?php
require_once dirname(__DIR__) . '/config/config.php';
clear_remember_cookie();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
redirect(with_lang(url('index.php')));
