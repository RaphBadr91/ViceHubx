<?php
/**
 * ViceHub X — Like / unlike (forum posts & fan-arts). POST + CSRF, retour local.
 */
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in() || !verify_csrf()) {
    redirect(with_lang(url('pages/forum.php')));
}
$kind = in_array($_POST['kind'] ?? '', ['post', 'fanart'], true) ? $_POST['kind'] : '';
$item = (int) ($_POST['id'] ?? 0);
if ($kind && $item) {
    like_toggle($kind, $item, (int) current_user()['id']);
}
// Retour : uniquement un chemin local (anti open-redirect)
$ret = (string) ($_POST['return'] ?? '/');
if ($ret === '' || $ret[0] !== '/' || strncmp($ret, '//', 2) === 0) {
    $ret = '/';
}
header('Location: ' . $ret . (strpos($ret, '#') === false ? '#item-' . $kind . $item : ''), true, 303);
exit;
