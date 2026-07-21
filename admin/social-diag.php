<?php
/**
 * ViceHub X — Diagnostic RÉSEAUX (pourquoi rien ne se poste ?).
 * Affiche l'état exact : prêt/pas prêt, jeton, plafond, verrou, file, date serveur.
 * Réservé à un administrateur connecté. À supprimer une fois le souci réglé.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/ai.php';
require_once ROOT_PATH . '/includes/social.php';
if (!is_logged_in() || !is_admin()) { http_response_code(403); exit('Accès réservé à l\'administration.'); }

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: text/plain; charset=utf-8');
social_ensure_table();

$q = static function (string $sql) {
    try { return db()->query($sql)->fetchColumn(); } catch (Throwable $e) { return 'ERR:' . $e->getMessage(); }
};

echo "=== ÉTAT DE CONNEXION ===\n";
echo "social_auto (auto-publier)   : " . get_setting('social_auto', '0') . "\n";
echo "social_fb_enabled            : " . get_setting('social_fb_enabled', '0') . "\n";
echo "social_ig_enabled            : " . get_setting('social_ig_enabled', '0') . "\n";
echo "fb_page_id                   : " . (social_fb_page() !== '' ? social_fb_page() : '*** VIDE ***') . "\n";
echo "fb_page_token                : " . (social_fb_token() !== '' ? '(' . strlen(social_fb_token()) . ' caractères)' : '*** VIDE ***') . "\n";
echo "ig_user_id                   : " . (social_ig_user() !== '' ? social_ig_user() : '*** VIDE ***') . "\n";
echo "\n";
echo "social_fb_ready()            : " . (social_fb_ready() ? 'OUI ✅' : 'NON ❌') . "\n";
echo "social_ig_ready()            : " . (social_ig_ready() ? 'OUI ✅' : 'NON ❌') . "\n";
echo "social_any_ready()           : " . (social_any_ready() ? 'OUI ✅' : 'NON ❌') . "\n";

echo "\n=== PLAFOND / VERROU ===\n";
$cap = max(1, (int) get_setting('social_daily_max', '10'));
echo "plafond/jour (daily_max)     : $cap\n";
echo "posté AUJOURD'HUI Facebook   : " . social_posted_today('facebook') . " / $cap\n";
echo "posté AUJOURD'HUI Instagram  : " . social_posted_today('instagram') . " / $cap\n";
$lock = (int) get_setting('social_lock', '0');
echo "verrou (lock)                : " . ($lock > 0 ? $lock . ' (il y a ' . (time() - $lock) . ' s)' : 'libre') . "\n";

echo "\n=== FILE D'ATTENTE ===\n";
echo "en attente Facebook          : " . $q("SELECT COUNT(*) FROM social_queue WHERE status='pending' AND platform='facebook'") . "\n";
echo "en attente Instagram         : " . $q("SELECT COUNT(*) FROM social_queue WHERE status='pending' AND platform='instagram'") . "\n";
echo "postés (total)               : " . $q("SELECT COUNT(*) FROM social_queue WHERE status='posted'") . "\n";
echo "en erreur (total)            : " . $q("SELECT COUNT(*) FROM social_queue WHERE status='error'") . "\n";

echo "\n=== DERNIÈRE ERREUR ENREGISTRÉE ===\n";
$err = $q("SELECT error FROM social_queue WHERE status='error' AND error IS NOT NULL ORDER BY id DESC LIMIT 1");
echo ($err !== false && $err !== null && $err !== '') ? $err . "\n" : "(aucune)\n";

echo "\n=== VÉRIF ARTICLE EN ATTENTE (jointure) ===\n";
$sample = $q("SELECT q.article_id FROM social_queue q WHERE q.status='pending' ORDER BY q.id DESC LIMIT 1");
echo "1er article_id en attente    : " . ($sample !== false ? $sample : '(aucun)') . "\n";
if ($sample !== false && $sample !== null && ctype_digit((string) $sample)) {
    $ok = $q("SELECT COUNT(*) FROM articles WHERE id=" . (int) $sample . " AND status='published'");
    echo "cet article existe & publié  : " . ((int) $ok === 1 ? 'OUI ✅' : 'NON ❌ (jointure échoue → pas posté)') . "\n";
}

echo "\n=== SERVEUR ===\n";
echo "date/heure serveur           : " . date('Y-m-d H:i:s') . " (" . date_default_timezone_get() . ")\n";
echo "CURDATE() base               : " . $q("SELECT CURDATE()") . "\n";
