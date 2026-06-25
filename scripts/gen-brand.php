<?php
/**
 * ViceHub X — Générateur d'identité de marque (logo, réseaux, favicon) via GD.
 * Produit des PNG nets, sans dépendance réseau, dans public/assets/img/.
 *   Usage : php scripts/gen-brand.php
 */
$ROOT = dirname(__DIR__);
$IMG  = $ROOT . '/public/assets/img';
$BRAND = $IMG . '/brand';
@mkdir($BRAND, 0775, true);

$FONT  = '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf';
$FONTI = '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf';
if (!is_file($FONT)) { $FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'; }
if (!is_file($FONTI)) { $FONTI = $FONT; }

function col($im, $r, $g, $b, $a = 0) { return imagecolorallocatealpha($im, $r, $g, $b, $a); }

/** Dégradé vertical du ciel + sol. */
function sky($im, $w, $h, $horizon) {
    for ($y = 0; $y < $horizon; $y++) {
        $t = $y / max(1, $horizon);
        $r = (int)(26 + (255 - 26) * pow($t, 1.5));
        $g = (int)(8 + (90 - 8) * pow($t, 2.2));
        $b = (int)(48 + (90 - 48) * (1 - $t));
        imagefilledrectangle($im, 0, $y, $w, $y, col($im, $r, min(255,$g), $b));
    }
    for ($y = $horizon; $y < $h; $y++) {
        $t = ($y - $horizon) / max(1, $h - $horizon);
        $r = (int)(26 * (1 - $t) + 7);
        $b = (int)(46 * (1 - $t) + 15);
        imagefilledrectangle($im, 0, $y, $w, $y, col($im, $r, 6, $b + 10));
    }
}

/** Soleil rétro avec bandes. */
function retroSun($im, $cx, $cy, $R) {
    for ($y = -$R; $y <= $R; $y++) {
        $t = ($y + $R) / (2 * $R);
        $r = (int)(255);
        $g = (int)(230 * (1 - $t) + 50 * $t);
        $b = (int)(80 * (1 - $t) + 136 * $t);
        $half = (int) sqrt(max(0, $R * $R - $y * $y));
        imagefilledrectangle($im, $cx - $half, $cy + $y, $cx + $half, $cy + $y, col($im, $r, $g, $b));
    }
    // bandes sombres dans la moitié basse
    for ($i = 0; $i < 7; $i++) {
        $by = $cy + (int)($R * 0.16) + $i * (int)($R * 0.13);
        $half = (int) sqrt(max(0, $R * $R - ($by - $cy) * ($by - $cy)));
        imagefilledrectangle($im, $cx - $half, $by, $cx + $half, $by + max(2, (int)($R*0.03) + $i), col($im, 20, 8, 36));
    }
}

/** Grille néon en perspective. */
function neonGrid($im, $w, $h, $horizon, $pink, $cyan) {
    for ($i = -12; $i <= 12; $i++) {
        $x = $w / 2 + $i * ($w / 12);
        imageline($im, (int)($w/2), $horizon, (int)$x, $h, $cyan);
    }
    $n = 10;
    for ($i = 1; $i <= $n; $i++) {
        $p = $i / $n;
        $y = $horizon + (int)(pow($p, 2.2) * ($h - $horizon));
        imageline($im, 0, $y, $w, $y, $pink);
    }
}

/** Skyline silhouette. */
function skyline($im, $w, $h, $baseY, $dark) {
    $x = 0;
    $seedH = [0.5,0.8,0.35,0.95,0.6,0.45,0.85,0.4,0.7,0.55,0.9,0.5,0.75,0.6,0.4,0.8,0.5];
    $i = 0;
    while ($x < $w) {
        $bw = (int)($w * (0.04 + ($i % 3) * 0.012));
        $bh = (int)($h * 0.16 * $seedH[$i % count($seedH)]);
        imagefilledrectangle($im, $x, $baseY - $bh, $x + $bw - 2, $baseY, $dark);
        // quelques fenêtres
        $x += $bw + (int)($w * 0.006);
        $i++;
    }
}

/** Palmier silhouette simple. */
function palm($im, $x, $y, $s, $dark) {
    // tronc courbe
    $pts = [];
    for ($t = 0; $t <= 1; $t += 0.1) {
        $px = $x + (int)(sin($t * 1.2) * $s * 0.18);
        $py = $y - (int)($t * $s);
        $pts[] = [$px, $py];
    }
    for ($k = 0; $k < count($pts) - 1; $k++) {
        imagesetthickness($im, max(2, (int)($s * 0.045)));
        imageline($im, $pts[$k][0], $pts[$k][1], $pts[$k+1][0], $pts[$k+1][1], $dark);
    }
    imagesetthickness($im, 1);
    $top = end($pts);
    // frondes
    $angles = [200, 230, 260, 290, 320, 340];
    foreach ($angles as $a) {
        $rad = deg2rad($a);
        $ex = $top[0] + (int)(cos($rad) * $s * 0.5);
        $ey = $top[1] + (int)(sin($rad) * $s * 0.42);
        $mx = ($top[0] + $ex) / 2 + (int)(cos($rad + 1.57) * $s * 0.08);
        $my = ($top[1] + $ey) / 2 + (int)(sin($rad + 1.57) * $s * 0.08);
        $poly = [$top[0], $top[1], (int)$mx, (int)$my, $ex, $ey];
        imagesetthickness($im, max(2, (int)($s * 0.03)));
        imageline($im, $top[0], $top[1], (int)$ex, (int)$ey, $dark);
    }
    imagesetthickness($im, 1);
}

/** Texte TTF avec halo néon. */
function glowText($im, $font, $size, $x, $y, $text, $rgb, $glow) {
    for ($i = 0; $i < 6; $i++) {
        $a = 78 - $i * 8;
        $g = imagecolorallocatealpha($im, $glow[0], $glow[1], $glow[2], max(40, $a));
        foreach ([[-2,0],[2,0],[0,-2],[0,2],[2,2],[-2,-2]] as $o) {
            imagettftext($im, $size, 0, $x + $o[0], $y + $o[1], $g, $font, $text);
        }
    }
    $c = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    imagettftext($im, $size, 0, $x, $y, $c, $font, $text);
    return imagettfbbox($size, 0, $font, $text);
}
function textW($size, $font, $text) { $b = imagettfbbox($size, 0, $font, $text); return abs($b[2] - $b[0]); }

/** Construit la scène Vice City (sans texte). */
function buildScene($w, $h) {
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    $horizon = (int)($h * 0.62);
    sky($im, $w, $h, $horizon);
    $pink = imagecolorallocatealpha($im, 255, 46, 136, 70);
    $cyan = imagecolorallocatealpha($im, 43, 214, 255, 78);
    $dark = imagecolorallocate($im, 12, 8, 28);
    retroSun($im, (int)($w/2), (int)($horizon * 0.74), (int)(min($w,$h) * 0.20));
    neonGrid($im, $w, $h, $horizon, $pink, $cyan);
    skyline($im, $w, $h, $horizon + 1, $dark);
    palm($im, (int)($w*0.08), $horizon + (int)($h*0.02), (int)($h*0.34), $dark);
    palm($im, (int)($w*0.92), $horizon + (int)($h*0.02), (int)($h*0.34), $dark);
    // vignette
    for ($i = 0; $i < 60; $i++) {
        $a = 90 - $i;
        if ($a < 0) break;
        $cc = imagecolorallocatealpha($im, 0, 0, 8, 127 - (int)($a/2));
        imagerectangle($im, $i, $i, $w - $i - 1, $h - $i - 1, $cc);
    }
    return $im;
}

/** Dessine le wordmark VICEHUB X centré sur l'image. */
function wordmark($im, $w, $cx, $cy, $font, $fonti, $size, $tagline = true) {
    // segments : VICE (blanc) HUB (rose)
    $wVice = textW($size, $font, 'VICE');
    $wHub  = textW($size, $font, 'HUB');
    $wX    = textW($size * 1.25, $fonti, 'X');
    $gap   = (int)($size * 0.18);
    $total = $wVice + $wHub + $gap + $wX;
    $x = $cx - (int)($total / 2);
    $base = $cy;
    glowText($im, $font, $size, $x, $base, 'VICE', [255,255,255], [43,214,255]);
    glowText($im, $font, $size, $x + $wVice, $base, 'HUB', [255,46,136], [255,46,136]);
    glowText($im, $fonti, $size * 1.25, $x + $wVice + $wHub + $gap, $base + (int)($size*0.06), 'X', [43,214,255], [43,214,255]);
    if ($tagline) {
        $tag = 'G T A   V I   ·   N E W S';
        $ts = max(10, (int)($size * 0.22));
        $tw = textW($ts, $font, $tag);
        $tc = imagecolorallocate($im, 255, 209, 102);
        imagettftext($im, $ts, 0, $cx - (int)($tw/2), $base + (int)($size*0.55), $tc, $font, $tag);
    }
}

/* ---- Asset 1 : carré réseaux (Instagram / Facebook profil) 1080² ---- */
$sq = buildScene(1080, 1080);
wordmark($sq, 1080, 540, 560, $FONT, $FONTI, 96, true);
imagepng($sq, "$BRAND/logo-square.png");
// version 512 (PWA / favicon large) et 192
$i512 = imagescale($sq, 512, 512); imagepng($i512, "$IMG/icon-512.png");
$i192 = imagescale($sq, 192, 192); imagepng($i192, "$IMG/icon-192.png");
$i180 = imagescale($sq, 180, 180); imagepng($i180, "$IMG/apple-touch-icon.png");

/* ---- Asset 2 : bannière Facebook (1640 x 624) ---- */
$cov = buildScene(1640, 624);
wordmark($cov, 1640, 820, 330, $FONT, $FONTI, 104, true);
imagepng($cov, "$BRAND/fb-cover.png");

/* ---- Asset 3 : wordmark large sur fond sombre (1600 x 500) ---- */
$wide = buildScene(1600, 500);
wordmark($wide, 1600, 800, 280, $FONT, $FONTI, 110, true);
imagepng($wide, "$BRAND/logo-wide.png");

/* ---- Asset 4 : emblème favicon (carré 256, monogramme) ---- */
$fav = imagecreatetruecolor(256, 256);
imagealphablending($fav, true);
sky($fav, 256, 256, 168);
retroSun($fav, 128, 120, 70);
skyline($fav, 256, 256, 169, imagecolorallocate($fav, 12, 8, 28));
// gros X néon
glowText($fav, $FONTI, 130, 78, 200, 'X', [255,255,255], [255,46,136]);
imagepng($fav, "$IMG/favicon-256.png");
$f32 = imagescale($fav, 32, 32); imagepng($f32, "$IMG/favicon-32.png");

echo "OK — assets de marque générés dans public/assets/img/ et /brand :\n";
foreach (["$BRAND/logo-square.png","$BRAND/fb-cover.png","$BRAND/logo-wide.png","$IMG/icon-512.png","$IMG/icon-192.png","$IMG/apple-touch-icon.png","$IMG/favicon-256.png","$IMG/favicon-32.png"] as $f) {
    echo "  " . str_replace($GLOBALS['ROOT'] ?? dirname(__DIR__), '', $f) . " (" . (is_file($f) ? filesize($f) . ' o' : 'ERR') . ")\n";
}
