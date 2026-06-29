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

function fn_allowed(string $f): bool
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return function_exists($f) && !in_array($f, $disabled, true);
}

/** Exécute une commande shell via la 1re méthode dispo. Retourne [sortie, peutExécuter]. */
function run_cmd(string $cmd): array
{
    if (fn_allowed('shell_exec')) { return [(string) @shell_exec($cmd), true]; }
    if (fn_allowed('exec'))       { $o = []; @exec($cmd . ' 2>&1', $o); return [implode("\n", $o), true]; }
    if (fn_allowed('proc_open')) {
        $p = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($p)) {
            $o = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
            return [$o, true];
        }
    }
    return ['', false];
}

function find_ffmpeg(): ?string
{
    foreach (['command -v ffmpeg', 'which ffmpeg', 'type -p ffmpeg'] as $probe) {
        [$out, ] = run_cmd($probe . ' 2>/dev/null');
        $p = trim((string) strtok((string) $out, "\n"));
        if ($p !== '' && @is_file($p)) { return $p; }
    }
    foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/bin/ffmpeg',
              '/opt/cpanel/ea-ffmpeg/root/usr/bin/ffmpeg', '/usr/local/cpanel/3rdparty/bin/ffmpeg'] as $c) {
        if (@is_file($c)) { return $c; }
    }
    [$out, ] = run_cmd('ls /usr/bin/ffmpeg /usr/local/bin/ffmpeg /opt/*/ffmpeg /opt/*/*/ffmpeg 2>/dev/null');
    foreach (explode("\n", trim((string) $out)) as $line) {
        $line = trim($line);
        if ($line !== '' && @is_file($line)) { return $line; }
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

$execMethod = fn_allowed('shell_exec') ? 'shell_exec' : (fn_allowed('exec') ? 'exec' : (fn_allowed('proc_open') ? 'proc_open' : 'AUCUNE (exécution shell bloquée)'));
$ffmpeg = find_ffmpeg();
$log[] = 'Méthode shell : ' . $execMethod;
$log[] = 'ffmpeg : ' . ($ffmpeg ?: 'INTROUVABLE (repli sur le 1er plan)');

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
        // Chaque plan tronqué à 3,5 s (montage ~17 s) + compression web légère → fluide.
        for ($i = 0; $i < $n; $i++) { $fc .= "[$i:v]trim=0:3.5,setpts=PTS-STARTPTS,scale=1280:720,setsar=1,fps=30,format=yuv420p[v$i];"; }
        for ($i = 0; $i < $n; $i++) { $fc .= "[v$i]"; }
        $fc .= "concat=n=$n:v=1:a=0[outv]";
        $cmd = escapeshellarg($ffmpeg) . ' -y -loglevel error' . $inputs
            . ' -filter_complex ' . escapeshellarg($fc)
            . ' -map ' . escapeshellarg('[outv]') . ' -an -c:v libx264 -crf 28 -maxrate 2500k -bufsize 5000k'
            . ' -preset veryfast -pix_fmt yuv420p -movflags +faststart '
            . escapeshellarg($out) . ' 2>&1';
        [$res, ] = run_cmd($cmd);
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

// Sans ffmpeg : on télécharge les 5 plans → lecture en PLAYLIST (boucle dans le navigateur).
if ($method === null) {
    foreach (glob($videoDir . '/hero.mp4') ?: [] as $f) { @unlink($f); } // retire un montage single éventuel
    $dl = 0;
    foreach ($CLIPS as $i => $u) {
        $d = grab($u);
        if ($d !== null) {
            file_put_contents($videoDir . '/hero-' . ($i + 1) . '.mp4', $d);
            $dl++;
            $log[] = 'Plan ' . ($i + 1) . ' : téléchargé (' . round(strlen($d) / 1048576, 1) . ' Mo)';
        } else {
            $log[] = 'Plan ' . ($i + 1) . ' : ÉCHEC';
        }
    }
    if ($dl > 0) {
        $method = 'playlist';
        $log[] = "Playlist : {$dl} scènes prêtes (défilement en boucle dans le navigateur).";
    }
} else {
    // Montage réussi : on retire les éventuels plans de playlist d'un run précédent.
    foreach (glob($videoDir . '/hero-*.mp4') ?: [] as $f) { @unlink($f); }
}

$okFinal = ($method === 'montage' && @is_file($out) && filesize($out) > 200000)
        || ($method === 'playlist' && count(glob($videoDir . '/hero-*.mp4') ?: []) > 0);
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
