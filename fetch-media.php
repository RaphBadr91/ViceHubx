<?php
/**
 * ViceHub X — Rapatriement des médias du CDN vers l'hébergement (1 clic).
 *
 * Télécharge la vidéo d'accueil + toutes les images (scènes, véhicules, boutique,
 * marque, wallpapers) depuis le CDN vers les dossiers locaux du site. Ensuite, le
 * site sert les médias EN LOCAL (rapide, permanent, vidéo qui joue comme en local).
 *
 * À ouvrir UNE fois : https://vicehubx.com/fetch-media.php  → puis SUPPRIMER ce fichier.
 * (Fonctionne aussi en CLI : php fetch-media.php)
 */
require_once __DIR__ . '/config/config.php';

@set_time_limit(0);
@ignore_user_abort(true);

$isCli = (PHP_SAPI === 'cli');

/* --- Construit la liste { chemin local => URL CDN } --------------------- */
$targets = [];

// 1) Vidéo d'accueil (hero) — la pièce maîtresse.
$hero = cdn_url('hero.mp4');
if ($hero !== '') {
    $targets['public/assets/video/hero.mp4'] = $hero;
}

// 2) Images du cdn_map : on range selon la famille (préfixe du nom).
$map = is_file(ROOT_PATH . '/config/cdn_map.php') ? require ROOT_PATH . '/config/cdn_map.php' : [];
foreach ($map as $key => $url) {
    if ($key === 'hero.mp4' || !is_string($url) || $url === '') {
        continue;
    }
    if (preg_match('/^brand-/', $key)) {
        $dir = 'public/assets/img/brand';
    } elseif (preg_match('/^(poster-|tshirt|hoodie|cap|mug|mousepad|console|game-case|shop-)/', $key)) {
        $dir = 'public/assets/img/shop';
    } else {
        $dir = 'public/assets/img/scenes'; // scènes + véhicules (veh-*)
    }
    $targets["$dir/$key"] = $url;
}

// 3) Chemins exacts stockés en base (produits, véhicules, articles) — fait foi.
try {
    $rows = db()->query(
        "SELECT image FROM products WHERE image LIKE '/public/%'
         UNION SELECT image FROM vehicles WHERE image LIKE '/public/%'
         UNION SELECT image FROM articles WHERE image LIKE '/public/%'"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $img) {
        $rel = ltrim((string) $img, '/');
        $url = cdn_url(basename($rel));
        if ($url !== '') {
            $targets[$rel] = $url;
        }
    }
} catch (Throwable $e) { /* base indispo : on continue avec le cdn_map */ }

// 4) Wallpapers (boutique) → storage/wallpapers/<nom>.<ext>
$wp = is_file(ROOT_PATH . '/config/wallpapers.php') ? require ROOT_PATH . '/config/wallpapers.php' : [];
foreach ($wp as $name => $url) {
    if (!is_string($url) || $url === '') {
        continue;
    }
    $ext = preg_match('/\.(jpe?g|webp)(\?|$)/i', $url, $m) ? strtolower($m[1]) : 'png';
    $targets["storage/wallpapers/{$name}.{$ext}"] = $url;
}

/* --- Téléchargement ----------------------------------------------------- */
function vh_fetch(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_USERAGENT => 'ViceHubX-Media/1.0',
        ]);
        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($data !== false && $code < 400 && strlen($data) > 500) ? $data : null;
    }
    $data = @file_get_contents($url);
    return ($data !== false && strlen($data) > 500) ? $data : null;
}

$ok = 0; $skip = 0; $fail = 0; $log = [];
foreach ($targets as $rel => $url) {
    $dest = ROOT_PATH . '/' . $rel;
    // Le hero se rafraîchit TOUJOURS (pour pouvoir changer de vidéo facilement).
    $isHero = ($rel === 'public/assets/video/hero.mp4');
    // Vidéo : on exige une vraie taille (sinon = téléchargement partiel à refaire).
    $minKeep = preg_match('/\.(mp4|webm|mov)$/i', $rel) ? 200000 : 1000;
    if (!$isHero && is_file($dest) && filesize($dest) > $minKeep) { $skip++; continue; }
    @mkdir(dirname($dest), 0755, true);
    $data = vh_fetch($url);
    if ($data !== null && @file_put_contents($dest, $data) !== false) {
        $ok++; $log[] = "OK   $rel (" . round(strlen($data) / 1024) . " Ko)";
    } else {
        $fail++; $log[] = "ÉCHEC $rel";
    }
}
// 5) Illustrer les articles sans image (visuel des cartes) avec une scène Vice City.
$illustrated = 0;
try {
    require_once ROOT_PATH . '/includes/ai.php'; // ai_pick_image()
    $arts = db()->query("SELECT id, title FROM articles WHERE image IS NULL OR image = ''")->fetchAll(PDO::FETCH_ASSOC);
    $updImg = db()->prepare('UPDATE articles SET image = ? WHERE id = ?');
    foreach ($arts as $a) {
        $key = ai_pick_image('', (string) $a['title']); // scène/véhicule selon le titre
        $updImg->execute(['/public/assets/img/scenes/' . $key, (int) $a['id']]);
        $illustrated++;
    }
} catch (Throwable $e) { /* base indispo : on ignore */ }

$summary = "Téléchargés : {$ok} · déjà présents : {$skip} · échecs : {$fail} · articles illustrés : {$illustrated} (médias : " . count($targets) . ")";

if ($isCli) {
    echo $summary . "\n" . implode("\n", $log) . "\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Rapatriement des médias</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(620px,94vw)">
    <h1 style="text-align:center;font-size:1.2rem">Rapatriement des médias 🌴</h1>
    <div class="alert alert--<?= $fail === 0 ? 'ok' : 'err' ?>" style="margin:1rem 0"><?= e($summary) ?></div>
    <?php if ($ok > 0): ?>
        <p class="muted">✓ La vidéo d'accueil et les images sont maintenant <strong>sur ton serveur</strong>. Recharge le site (Ctrl+Maj+R) : le hero joue la vidéo en local.</p>
    <?php endif; ?>
    <?php if ($fail > 0): ?>
        <p class="muted">⚠️ Certains fichiers ont échoué (CDN momentanément indispo). Tu peux <strong>relancer cette page</strong> : elle ne retélécharge que ce qui manque.</p>
        <form method="post"><button class="btn btn--ghost" type="submit" style="width:100%;justify-content:center">Relancer les téléchargements manquants</button></form>
    <?php endif; ?>
    <pre style="max-height:280px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.72rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
    <a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">Voir le site →</a>
    <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime ce fichier <code>fetch-media.php</code></strong> une fois terminé.</p>
</div></div></body></html>
