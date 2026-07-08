<?php
/**
 * ViceHub X — Génère la belle image de partage social (og:image, 1200×630).
 * Prend une VRAIE scène cinématographique Vice City (bannière IA dédiée, sinon une
 * scène locale), la recadre, l'assombrit en bas, et pose un texte net (polices TTF).
 *
 * À ouvrir UNE fois (connecté en admin) : https://vicehubx.com/make-og.php
 */
require_once __DIR__ . '/config/config.php';
setup_guard(); // 🔒 admin connecté (ou VICEHUB_SETUP=1)
@set_time_limit(0);
@ini_set('memory_limit', '512M');

$W = 1200; $H = 630;
$fontDir = ROOT_PATH . '/public/assets/fonts';
$fHead = $fontDir . '/BigShoulders-Bold.ttf';
$fBody = $fontDir . '/BricolageGrotesque-Regular.ttf';
$fMono = $fontDir . '/Tektur-Medium.ttf';

$log = [];
$ok  = false;

// Sources d'image, par ordre de préférence (bannière IA dédiée puis belles scènes locales).
$sources = [
    'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260708_092603_af4f835a-d84c-4bf8-8f69-22e75adbee47.png',
    ROOT_PATH . '/public/assets/img/scenes/ocean-drive.png',
    ROOT_PATH . '/public/assets/img/scenes/nightlife.png',
    ROOT_PATH . '/public/assets/img/scenes/rain-neon.png',
    ROOT_PATH . '/public/assets/img/scenes/night.png',
];

function og_fetch(string $src): ?string
{
    if (preg_match('#^https?://#', $src)) {
        if (function_exists('curl_init')) {
            $ch = curl_init($src);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 15]);
            $d = curl_exec($ch); $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            return ($d !== false && $c < 400 && strlen($d) > 1000) ? $d : null;
        }
        $d = @file_get_contents($src);
        return ($d !== false && strlen($d) > 1000) ? $d : null;
    }
    return is_file($src) ? (@file_get_contents($src) ?: null) : null;
}

if (!function_exists('imagettftext') || !is_file($fHead)) {
    $log[] = '✗ GD/FreeType ou polices manquantes.';
} else {
    $bg = null; $used = '';
    foreach ($sources as $s) {
        $d = og_fetch($s);
        if ($d) { $bg = @imagecreatefromstring($d); if ($bg) { $used = basename($s); break; } }
    }
    if (!$bg) {
        $bg = imagecreatetruecolor($W, $H);
        for ($y = 0; $y < $H; $y++) { imageline($bg, 0, $y, $W, $y, imagecolorallocate($bg, (int) (40 + 70 * $y / $H), 18, (int) (80 + 60 * (1 - $y / $H)))); }
        $used = 'dégradé (repli)';
    }
    $log[] = 'Image de fond : ' . $used;

    // Canvas + recadrage « cover ».
    $canvas = imagecreatetruecolor($W, $H);
    imagealphablending($canvas, true);
    $sw = imagesx($bg); $sh = imagesy($bg);
    $scale = max($W / $sw, $H / $sh);
    $nw = (int) ceil($sw * $scale); $nh = (int) ceil($sh * $scale);
    imagecopyresampled($canvas, $bg, (int) (($W - $nw) / 2), (int) (($H - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);
    imagedestroy($bg);

    // Voile léger partout + fort en bas (lisibilité du texte).
    for ($y = 0; $y < $H; $y++) {
        $op = 0.20;
        if ($y > $H * 0.40) { $op += 0.64 * (($y - $H * 0.40) / ($H * 0.60)); }
        $op = min(0.84, $op);
        imagefilledrectangle($canvas, 0, $y, $W, $y, imagecolorallocatealpha($canvas, 6, 4, 14, (int) round(127 * (1 - $op))));
    }

    $center = static function ($size, $font, $text) use ($W) {
        $bb = imagettfbbox($size, 0, $font, $text);
        return (int) (($W - ($bb[2] - $bb[0])) / 2 - $bb[0]);
    };
    $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 55);
    $white  = imagecolorallocate($canvas, 255, 255, 255);
    $cyan   = imagecolorallocate($canvas, 43, 214, 255);
    $gold   = imagecolorallocate($canvas, 255, 209, 102);
    $grey   = imagecolorallocate($canvas, 214, 208, 226);
    $greyd  = imagecolorallocate($canvas, 170, 164, 186);

    // Titre « VICEHUB X » (bicolore), centré.
    $ts = 82;
    $t1 = 'VICEHUB'; $t2 = ' X';
    $bb1 = imagettfbbox($ts, 0, $fHead, $t1);
    $bb2 = imagettfbbox($ts, 0, $fHead, $t2);
    $w1 = $bb1[2] - $bb1[0]; $w2 = $bb2[2] - $bb2[0];
    $sx = (int) (($W - ($w1 + $w2)) / 2);
    $ty = 415;
    imagettftext($canvas, $ts, 0, $sx + 3, $ty + 4, $shadow, $fHead, $t1);
    imagettftext($canvas, $ts, 0, $sx, $ty, $white, $fHead, $t1);
    imagettftext($canvas, $ts, 0, $sx + $w1 + 3, $ty + 4, $shadow, $fHead, $t2);
    imagettftext($canvas, $ts, 0, $sx + $w1, $ty, $cyan, $fHead, $t2);

    // Barre néon sous le titre.
    for ($x = (int) ($W / 2 - 190); $x < (int) ($W / 2 + 190); $x++) {
        $f = ($x - ($W / 2 - 190)) / 380;
        imageline($canvas, $x, $ty + 16, $x, $ty + 21, imagecolorallocate($canvas, (int) (255 - 212 * $f), (int) (46 + 168 * $f), (int) (136 + 119 * $f)));
    }

    // Sous-titre.
    $sub = 'Le média n°1 sur GTA VI & Vice City';
    imagettftext($canvas, 29, 0, $center(29, $fBody, $sub) + 2, 477, $shadow, $fBody, $sub);
    imagettftext($canvas, 29, 0, $center(29, $fBody, $sub), 475, $white, $fBody, $sub);

    // Date de sortie.
    $date = 'GTA VI  —  19 NOVEMBRE 2026';
    imagettftext($canvas, 33, 0, $center(33, $fMono, $date) + 2, 537, $shadow, $fMono, $date);
    imagettftext($canvas, 33, 0, $center(33, $fMono, $date), 535, $gold, $fMono, $date);

    // Rubriques + signature.
    $rub = 'News · Guides · Leaks · Carte de Leonida · Forum';
    imagettftext($canvas, 21, 0, $center(21, $fBody, $rub), 578, $grey, $fBody, $rub);
    $sig = 'Média fan indépendant & non officiel · vicehubx.com';
    imagettftext($canvas, 17, 0, $center(17, $fBody, $sig), 610, $greyd, $fBody, $sig);

    @mkdir(ROOT_PATH . '/public/assets/img/brand', 0755, true);
    $out = ROOT_PATH . '/public/assets/img/brand/og-share.jpg';
    if (imagejpeg($canvas, $out, 90)) { $ok = true; $log[] = 'Image de partage : OK (' . round(filesize($out) / 1024) . ' Ko)'; }
    imagedestroy($canvas);
}

$bust = $ok ? ('?v=' . filemtime(ROOT_PATH . '/public/assets/img/brand/og-share.jpg')) : '';
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Image de partage</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(720px,96vw)">
<h1 style="text-align:center;font-size:1.2rem">Image de partage social 🖼️</h1>
<div class="alert alert--<?= $ok ? 'ok' : 'err' ?>" style="margin:1rem 0"><?= $ok ? '✓ Nouvelle image de partage générée (vraie scène Vice City + texte net).' : '✗ Échec — détail ci-dessous.' ?></div>
<?php if ($ok): ?>
<p class="muted">Aperçu (ce qui s'affichera sur WhatsApp / Facebook / X) :</p>
<img src="<?= e(asset('img/brand/og-share.jpg')) . $bust ?>" alt="Aperçu" style="width:100%;border-radius:12px;margin:.5rem 0 1rem;display:block">
<p class="muted" style="font-size:.85rem">Si elle te plaît : rien d'autre à faire, elle est déjà en place. Sinon dis-le-moi, je l'ajuste.</p>
<?php endif; ?>
<pre style="max-height:200px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.74rem"><?= e(implode("\n", $log)) ?></pre>
<a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">Voir le site →</a>
<p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Supprime <code>make-og.php</code> une fois l'image validée.</p>
</div></div></body></html>
