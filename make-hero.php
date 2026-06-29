<?php
/**
 * ViceHub X — (Re)crée la vidéo de fond du hero : le MONTAGE de 5 scènes
 * (trafic dense → course-poursuite → hélico → plage golden hour → survol aérien),
 * exactement comme la version d'origine, via ffmpeg sur le serveur.
 *
 * Repli intelligent : si ffmpeg n'est pas disponible, utilise le 1er plan
 * (trafic dense + passants) comme vidéo de hero.
 *
 * À ouvrir UNE fois : https://vicehubx.com/make-hero.php  → puis SUPPRIMER.
 */
require_once __DIR__ . '/config/config.php';
@set_time_limit(0);
@ignore_user_abort(true);

$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';
$CLIPS = [
    "$CDN/hf_20260622_163349_83ec4749-81dc-406e-a59c-bd961d63db73.mp4", // trafic dense + passants
    "$CDN/hf_20260622_131132_beb29382-3ff9-4429-933e-f3fdd03a9f55.mp4", // police-poursuite
    "$CDN/hf_20260622_131652_09f2e6b7-a3b4-482a-bc81-da676ae5e614.mp4", // hélico de nuit
    "$CDN/hf_20260622_093513_c9a90c6d-7340-4dfd-8963-410993e23456.mp4", // cruise plage golden hour
    "$CDN/hf_20260622_093551_0ec3d71f-b1a6-4ca4-9284-873fd2e1f381.mp4", // survol aérien
];
$POSTER = "$CDN/hf_20260622_130727_e28cfd20-aeab-4f18-9e8f-cace0ad0de40.png";

$videoDir = ROOT_PATH . '/public/assets/video';
$imgDir   = ROOT_PATH . '/public/assets/img';
@mkdir($videoDir, 0755, true);
@mkdir($imgDir, 0755, true);
$out = $videoDir . '/hero.mp4';

function grab(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 180, CURLOPT_CONNECTTIMEOUT => 20]);
        $d = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($d !== false && $code < 400 && strlen($d) > 500) ? $d : null;
    }
    $d = @file_get_contents($url);
    return ($d !== false && strlen($d) > 500) ? $d : null;
}

function find_ffmpeg(): ?string
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    $canShell = function_exists('shell_exec') && !in_array('shell_exec', $disabled, true);
    if ($canShell) {
        $p = trim((string) @shell_exec('command -v ffmpeg 2>/dev/null'));
        if ($p !== '' && @is_file($p)) {
            return $p;
        }
    }
    foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg', '/opt/cpanel/ea-ffmpeg/root/usr/bin/ffmpeg'] as $c) {
        if (@is_file($c)) {
            return $c;
        }
    }
    return null;
}

$log = [];
$method = null;

// Poster (image figée avant lecture).
$pdata = grab($POSTER);
if ($pdata !== null) {
    file_put_contents($imgDir . '/hero-poster.png', $pdata);
    $log[] = 'Poster : OK';
}

$ffmpeg = find_ffmpeg();
$log[] = 'ffmpeg : ' . ($ffmpeg ?: 'INDISPONIBLE (repli sur le 1er plan)');

if ($ffmpeg) {
    $tmp = sys_get_temp_dir() . '/vhhero_' . substr(md5($out . PHP_VERSION), 0, 8);
    @mkdir($tmp, 0755, true);
    $inputs = '';
    $okAll = true;
    foreach ($CLIPS as $i => $u) {
        $d = grab($u);
        if ($d === null) { $okAll = false; $log[] = "Clip $i : ÉCHEC"; break; }
        file_put_contents("$tmp/c$i.mp4", $d);
        $inputs .= ' -i ' . escapeshellarg("$tmp/c$i.mp4");
        $log[] = "Clip $i : téléchargé (" . round(strlen($d) / 1048576, 1) . " Mo)";
    }
    if ($okAll) {
        $n = count($CLIPS);
        $fc = '';
        for ($i = 0; $i < $n; $i++) { $fc .= "[$i:v]scale=1280:720,setsar=1,fps=30,format=yuv420p[v$i];"; }
        for ($i = 0; $i < $n; $i++) { $fc .= "[v$i]"; }
        $fc .= "concat=n=$n:v=1:a=0[outv]";
        $cmd = escapeshellarg($ffmpeg) . ' -y -loglevel error' . $inputs
            . ' -filter_complex ' . escapeshellarg($fc)
            . ' -map ' . escapeshellarg('[outv]') . ' -an -c:v libx264 -crf 23 -preset veryfast -movflags +faststart '
            . escapeshellarg($out) . ' 2>&1';
        $res = function_exists('shell_exec') ? @shell_exec($cmd) : null;
        if (@is_file($out) && filesize($out) > 200000) {
            $method = 'montage';
            $log[] = 'Montage : OK (' . round(filesize($out) / 1048576, 1) . ' Mo)';
        } else {
            $log[] = 'Montage : échec ffmpeg → ' . trim((string) $res);
        }
    }
    foreach (glob("$tmp/*.mp4") ?: [] as $f) { @unlink($f); }
    @rmdir($tmp);
}

// Repli : le 1er plan (trafic dense) comme hero.
if ($method === null) {
    $d = grab($CLIPS[0]);
    if ($d !== null) {
        file_put_contents($out, $d);
        $method = 'clip';
        $log[] = 'Repli : 1er plan (trafic dense) installé comme hero.';
    } else {
        $log[] = 'Repli : ÉCHEC du téléchargement.';
    }
}

$okFinal = (@is_file($out) && filesize($out) > 200000);
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Montage du hero</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(620px,94vw)">
<h1 style="text-align:center;font-size:1.2rem">Vidéo du hero 🎬</h1>
<div class="alert alert--<?= $okFinal ? 'ok' : 'err' ?>" style="margin:1rem 0">
    <?= $okFinal
        ? ($method === 'montage' ? '✓ Montage des 5 scènes recréé avec succès !' : '✓ Vidéo du hero installée (1er plan — ffmpeg indispo pour le montage complet).')
        : '✗ Échec. Regarde le détail ci-dessous.' ?>
</div>
<?php if ($okFinal): ?>
<p class="muted">Recharge le site en <strong>navigation privée</strong> (Ctrl+Maj+N) pour voir le hero animé.</p>
<?php endif; ?>
<?php if ($okFinal && $method === 'clip'): ?>
<p class="muted" style="font-size:.85rem">ℹ️ ffmpeg n'est pas dispo sur ce serveur → on a mis le 1er plan du montage (le plus représentatif). Pour le montage complet des 5 scènes, il faudrait activer ffmpeg (support O2Switch).</p>
<?php endif; ?>
<pre style="max-height:260px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.74rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
<a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">Voir le site →</a>
<p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>make-hero.php</code></strong> une fois fini.</p>
</div></div></body></html>
