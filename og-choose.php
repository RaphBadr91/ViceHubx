<?php
/**
 * ViceHub X — Choix de l'image de partage social (og:image).
 * Affiche plusieurs scènes Vice City générées par IA ; tu cliques sur ta préférée
 * et elle devient l'image partagée sur WhatsApp / Facebook / X. Aucune retouche,
 * aucun texte : la beauté de l'image brute.
 *
 * À ouvrir (connecté en admin) : https://vicehubx.com/og-choose.php
 */
require_once __DIR__ . '/config/config.php';
setup_guard(); // 🔒 admin connecté (ou VICEHUB_SETUP=1)
@set_time_limit(0);
@ini_set('memory_limit', '512M');

// Scènes candidates (générées par IA — même CDN que le site).
$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';
$CANDIDATES = [
    "$CDN/hf_20260708_093855_e1f78e43-d3b8-479e-ba71-1eeee5b082db.png",
    "$CDN/hf_20260708_093855_fb47d3b0-4c9c-49b4-aefd-5ba4f916479c.png",
    "$CDN/hf_20260708_092603_af4f835a-d84c-4bf8-8f69-22e75adbee47.png",
    "$CDN/hf_20260708_092604_4977a956-dc34-412e-8e99-996320f58eba.png",
];

$W = 1200; $H = 630;
$done = false; $err = '';

if (isset($_GET['pick'])) {
    $i = (int) $_GET['pick'];
    if (!isset($CANDIDATES[$i])) {
        $err = 'Choix invalide.';
    } else {
        $data = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($CANDIDATES[$i]);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60]);
            $data = curl_exec($ch); if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) { $data = false; } curl_close($ch);
        } else {
            $data = @file_get_contents($CANDIDATES[$i]);
        }
        $src = ($data && strlen($data) > 1000) ? @imagecreatefromstring($data) : null;
        if (!$src) {
            $err = 'Téléchargement de l’image impossible.';
        } else {
            $canvas = imagecreatetruecolor($W, $H);
            $sw = imagesx($src); $sh = imagesy($src);
            $scale = max($W / $sw, $H / $sh);
            $nw = (int) ceil($sw * $scale); $nh = (int) ceil($sh * $scale);
            imagecopyresampled($canvas, $src, (int) (($W - $nw) / 2), (int) (($H - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);
            @mkdir(ROOT_PATH . '/public/assets/img/brand', 0755, true);
            imagejpeg($canvas, ROOT_PATH . '/public/assets/img/brand/og-share.jpg', 92);
            imagedestroy($canvas); imagedestroy($src);
            $done = true;
        }
    }
}
$bust = '?v=' . time();
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Choix de l'image de partage</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<style>.oggrid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.ogcard{border-radius:14px;overflow:hidden;border:1px solid var(--glass-brd);background:rgba(0,0,0,.3)}.ogcard img{width:100%;display:block;aspect-ratio:1200/630;object-fit:cover}.ogcard .btn{width:100%;justify-content:center;border-radius:0}@media(max-width:640px){.oggrid{grid-template-columns:1fr}}</style>
</head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(920px,96vw)">
<h1 style="text-align:center;font-size:1.2rem">Image de partage — choisis ta préférée 🖼️</h1>
<?php if ($done): ?>
    <div class="alert alert--ok" style="margin:1rem 0">✅ C'est fait ! Cette image s'affichera désormais sur WhatsApp / Facebook / X.</div>
    <img src="<?= e(asset('img/brand/og-share.jpg')) . $bust ?>" style="width:100%;border-radius:12px;display:block;margin-bottom:1rem" alt="Image choisie">
    <p class="muted">Tu peux <a class="link-all" href="<?= e(url('og-choose.php')) ?>">en choisir une autre</a> si tu changes d'avis.</p>
<?php else: ?>
    <?php if ($err): ?><div class="alert alert--err" style="margin:1rem 0"><?= e($err) ?></div><?php endif; ?>
    <p class="muted" style="margin:.4rem 0 1rem">Clique sur <strong>« Choisir celle-ci »</strong> sous la scène qui te plaît le plus.</p>
    <div class="oggrid">
        <?php foreach ($CANDIDATES as $i => $u): ?>
            <div class="ogcard">
                <img src="<?= e($u) ?>" alt="Scène <?= $i + 1 ?>" loading="lazy">
                <a class="btn btn--primary" href="<?= e(url('og-choose.php?pick=' . $i)) ?>">Choisir celle-ci</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Supprime <code>og-choose.php</code> une fois l'image choisie.</p>
</div></div></body></html>
