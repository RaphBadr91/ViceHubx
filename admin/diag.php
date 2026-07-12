<?php
/**
 * ViceHub X — DIAGNOSTIC TEMPORAIRE (à supprimer après usage).
 * Force l'affichage des erreurs, vide OPcache et vérifie l'état réel du serveur
 * pour localiser une page 500 / « écran noir » après un déploiement.
 *   Accès : /admin/diag.php?key=<ai_tick_key>  (ou admin connecté).
 * NE RÉVÈLE AUCUN SECRET (ni mot de passe, ni clé API).
 */
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$out = [];
$out[] = '== ViceHub X — Diagnostic ==';
$out[] = 'PHP ' . PHP_VERSION;

// Charge le coeur AVANT toute sortie (sinon session_start casse) et capture les fatals.
$loadErr = null;
try {
    require_once __DIR__ . '/../config/config.php';
    require_once ROOT_PATH . '/includes/ai.php';   // fonctions de traduction (chargées à la demande d'ordinaire)
} catch (Throwable $e) {
    $loadErr = $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
}

header('Content-Type: text/plain; charset=utf-8');
if ($loadErr !== null) {
    echo implode("\n", $out) . "\n\n[FATAL au chargement] " . $loadErr . "\n";
    exit;
}
$out[] = '[OK] config.php + functions.php + ai.php chargés';

// --- Contrôle d'accès léger (clé cron) ---
$key      = (string) ($_GET['key'] ?? '');
$expected = (string) (getenv('AI_TICK_KEY') ?: get_setting('ai_tick_key', ''));
$adminOk  = function_exists('is_logged_in') && is_logged_in() && is_admin();
if (!$adminOk && ($expected === '' || !hash_equals($expected, $key))) {
    http_response_code(403);
    echo implode("\n", $out) . "\n\n[403] Ajoute ?key=TA_CLE_CRON (Admin → Articles IA, lien cron) ou connecte-toi en admin.\n";
    exit;
}

// --- OPcache : après « Update from Remote », l'ancien bytecode peut être servi
//     (functions.php d'avant → « Call to undefined function unique_slug » → 500).
//     On le VIDE : c'est souvent la correction directe de l'écran noir. ---
$out[] = '';
$out[] = '-- OPcache --';
if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(false);
    $out[] = 'activé : ' . (($st && !empty($st['opcache_enabled'])) ? 'oui' : 'non');
    $out[] = (function_exists('opcache_reset') && @opcache_reset())
        ? '✅ OPcache VIDÉ — recharge la page qui plantait, elle devrait remarcher.'
        : 'opcache_reset indisponible.';
} else {
    $out[] = 'OPcache non disponible sur ce PHP.';
}

// --- Fonctions récentes présentes ? (détecte un déploiement partiel / cache) ---
$out[] = '';
$out[] = '-- Fonctions attendues --';
foreach (['unique_slug', 'ai_translate_missing', 'ai_ensure_source_col', 'product_categories', 'price_html'] as $fn) {
    $out[] = $fn . ' : ' . (function_exists($fn) ? 'OK' : '❌ ABSENTE');
}

// --- DB + colonnes attendues (les manquantes = source possible de 500) ---
$out[] = '';
try {
    $v = db()->query('SELECT VERSION()')->fetchColumn();
    $out[] = "[OK] DB connectée — $v";
} catch (Throwable $e) {
    echo implode("\n", $out) . "\n[FATAL db] " . $e->getMessage() . "\n";
    exit;
}
$expect = [
    'products' => ['id','name','slug','category','subcategory','sale_type','stripe_price_id','digital_file','cta','active','lang'],
    'articles' => ['id','lang','slug','image_prompt','source_id','status','published_at'],
    'orders'   => ['id','email','amount_total','currency','status','delivered'],
    'users'    => ['id','username','display_name','password_hash','role'],
];
foreach ($expect as $table => $cols) {
    try {
        $have = db()->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'")->fetchAll(PDO::FETCH_COLUMN);
        if (!$have) { $out[] = "[TABLE MANQUANTE] $table"; continue; }
        $missing = array_values(array_diff($cols, $have));
        $out[] = "Table $table : " . ($missing ? '⚠️ MANQUANTES : ' . implode(', ', $missing) : 'OK');
    } catch (Throwable $e) {
        $out[] = "[ERR $table] " . $e->getMessage();
    }
}

// --- Tests réels des requêtes des pages qui plantent ---
$out[] = '';
$out[] = '-- Tests de requêtes --';
$try = function (string $label, callable $fn) use (&$out) {
    try { $fn(); $out[] = "[OK] $label"; }
    catch (Throwable $e) { $out[] = "[ERREUR] $label → " . $e->getMessage(); }
};
$try('admin/products: cta count', fn() => db()->query("SELECT COUNT(*) FROM products WHERE active=1 AND cta=1")->fetchColumn());
$try('shop: get_products(subcategory)', fn() => db()->query("SELECT * FROM products WHERE active=1 AND subcategory='ville' LIMIT 1")->fetchAll());
$try('articles: source_id', fn() => db()->query("SELECT source_id FROM articles LIMIT 1")->fetchAll());

$out[] = '';
$out[] = '== Fin du diagnostic ==';
$out[] = 'Copie-colle TOUT ce texte, puis supprime ce fichier (admin/diag.php).';
echo implode("\n", $out) . "\n";
