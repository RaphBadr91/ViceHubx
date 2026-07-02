<?php
/**
 * ViceHub X — Like / unlike (forum posts & fan-arts). POST + CSRF, retour local.
 */
require_once __DIR__ . '/config/config.php';

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || !empty($_POST['ajax']);
$json = static function (array $d) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in() || !verify_csrf()) {
    if ($isAjax) { http_response_code(403); $json(['ok' => false]); }
    redirect(with_lang(url('pages/forum.php')));
}
$kind = in_array($_POST['kind'] ?? '', ['post', 'fanart'], true) ? $_POST['kind'] : '';
$item = (int) ($_POST['id'] ?? 0);
$count = 0; $liked = false;
if ($kind && $item) {
    $uid = (int) current_user()['id'];
    like_toggle($kind, $item, $uid);
    $count = like_count($kind, $item);
    $liked = user_liked($kind, $item, $uid);
}

// Réponse AJAX : on met à jour sur place, SANS recharger la page.
if ($isAjax) {
    $json(['ok' => true, 'count' => $count, 'liked' => $liked]);
}

// Repli sans JS : retour local (anti open-redirect)
$ret = (string) ($_POST['return'] ?? '/');
if ($ret === '' || $ret[0] !== '/' || strncmp($ret, '//', 2) === 0) {
    $ret = '/';
}
header('Location: ' . $ret . (strpos($ret, '#') === false ? '#item-' . $kind . $item : ''), true, 303);
exit;
