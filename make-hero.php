<?php
/**
 * ViceHub X — (Re)crée la VIDÉO de fond du hero : le MONTAGE de 5 scènes
 * (trafic dense → course-poursuite → hélico → plage golden hour → survol aérien),
 * exactement comme la version d'origine, mais COMPRESSÉE pour le web (autoplay fluide).
 *
 * O2Switch n'a pas ffmpeg installé → ce script TÉLÉCHARGE un ffmpeg statique
 * (binaire autonome, aucune installation) directement sur le serveur, puis
 * l'utilise pour assembler + compresser le montage (~4-5 Mo, démarrage instantané).
 *
 * Repli automatique : si ffmpeg statique est impossible (extraction/téléchargement
 * bloqués), le site bascule tout seul sur le montage cinématique d'IMAGES (déjà géré
 * par index.php). Aucune page cassée.
 *
 * À ouvrir UNE fois : https://vicehubx.com/make-hero.php  → puis SUPPRIMER.
 */
require_once __DIR__ . '/config/config.php';
@set_time_limit(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '512M');

$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E';
$CLIPS = [
    "$CDN/hf_20260622_163349_83ec4749-81dc-406e-a59c-bd961d63db73.mp4", // trafic dense + passants
    "$CDN/hf_20260622_131132_beb29382-3ff9-4429-933e-f3fdd03a9f55.mp4", // police-poursuite
    "$CDN/hf_20260622_131652_09f2e6b7-a3b4-482a-bc81-da676ae5e614.mp4", // hélico de nuit (vue de haut)
    "$CDN/hf_20260622_093513_c9a90c6d-7340-4dfd-8963-410993e23456.mp4", // cruise plage golden hour
    "$CDN/hf_20260622_093551_0ec3d71f-b1a6-4ca4-9284-873fd2e1f381.mp4", // survol aérien (vue du ciel)
];
$POSTER = "$CDN/hf_20260622_130727_e28cfd20-aeab-4f18-9e8f-cace0ad0de40.png";

$videoDir = ROOT_PATH . '/public/assets/video';
$imgDir   = ROOT_PATH . '/public/assets/img';
$binDir   = ROOT_PATH . '/storage/bin';
@mkdir($videoDir, 0755, true);
@mkdir($imgDir, 0755, true);
@mkdir($binDir, 0755, true);
$out = $videoDir . '/hero.mp4';

/** Télécharge une URL en mémoire (petit fichier). */
function grab(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 25, CURLOPT_USERAGENT => 'ViceHubX/1.0']);
        $d = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($d !== false && $code < 400 && strlen($d) > 500) ? $d : null;
    }
    $d = @file_get_contents($url);
    return ($d !== false && strlen($d) > 500) ? $d : null;
}

/** Télécharge une URL directement vers un fichier (gros fichier, sans saturer la mémoire). */
function grab_to_file(string $url, string $dest): bool
{
    if (function_exists('curl_init')) {
        $fp = @fopen($dest, 'wb');
        if (!$fp) { return false; }
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 600, CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_USERAGENT => 'ViceHubX/1.0']);
        $ok = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($ok === false || $code >= 400 || (int) (@filesize($dest) ?: 0) < 100000) { @unlink($dest); return false; }
        return true;
    }
    $d = @file_get_contents($url);
    if ($d === false || strlen($d) < 100000) { return false; }
    return @file_put_contents($dest, $d) !== false;
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

function can_shell(): bool
{
    return fn_allowed('shell_exec') || fn_allowed('exec') || fn_allowed('proc_open');
}

/** Cherche un ffmpeg déjà présent sur le système. */
function find_system_ffmpeg(): ?string
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
    return null;
}

/** Vérifie qu'un binaire ffmpeg est exécutable (répond à -version). */
function verify_ffmpeg(string $bin): bool
{
    if (!@is_file($bin)) { return false; }
    @chmod($bin, 0755);
    [$v, ] = run_cmd(escapeshellarg($bin) . ' -version 2>&1');
    return stripos((string) $v, 'ffmpeg version') !== false;
}

/** Décompresse un .gz vers un fichier, en flux (faible mémoire), via zlib natif PHP. */
function gunzip_file(string $src, string $dest): bool
{
    if (!function_exists('gzopen')) { return false; }
    $in  = @gzopen($src, 'rb');
    $out = @fopen($dest, 'wb');
    if (!$in || !$out) { if ($in) { gzclose($in); } if ($out) { fclose($out); } return false; }
    while (!gzeof($in)) {
        $chunk = gzread($in, 262144);
        if ($chunk === false || $chunk === '') { break; }
        fwrite($out, $chunk);
    }
    gzclose($in); fclose($out);
    return (int) (@filesize($dest) ?: 0) > 1000000;
}

/**
 * Garantit un ffmpeg utilisable : système, sinon binaire statique déjà installé,
 * sinon télécharge un binaire statique autonome (eugeneware/ffmpeg-static, linux-x64).
 * On évite xz/tar (absents d'O2Switch) : binaire BRUT, ou .gz décompressé en PHP natif.
 */
function ensure_ffmpeg(array &$log): ?string
{
    global $binDir;

    $sys = find_system_ffmpeg();
    if ($sys) { $log[] = 'ffmpeg système : ' . $sys; return $sys; }

    $local = $binDir . '/ffmpeg';
    if (verify_ffmpeg($local)) { $log[] = 'ffmpeg statique (déjà installé) : ' . $local; return $local; }

    if (!can_shell()) { $log[] = 'ffmpeg statique : impossible (exécution shell bloquée par l\'hébergeur)'; return null; }

    $base = 'https://github.com/eugeneware/ffmpeg-static/releases/download/b6.1.1';

    // 1) Binaire BRUT (aucune décompression) — le plus fiable.
    $log[] = 'Téléchargement de ffmpeg statique (binaire brut, ~80 Mo, une seule fois)…';
    if (grab_to_file("$base/ffmpeg-linux-x64", $local) && verify_ffmpeg($local)) {
        $log[] = 'ffmpeg statique installé (brut) : ' . round((int) filesize($local) / 1048576, 1) . ' Mo';
        return $local;
    }
    @unlink($local);
    $log[] = 'Binaire brut indispo/non exécutable → essai version .gz…';

    // 2) Binaire .gz décompressé en PHP natif (gzopen) — pas besoin de xz ni tar.
    $gz = $binDir . '/ffmpeg.gz';
    if (grab_to_file("$base/ffmpeg-linux-x64.gz", $gz) && gunzip_file($gz, $local)) {
        @unlink($gz);
        if (verify_ffmpeg($local)) {
            $log[] = 'ffmpeg statique installé (gzip) : ' . round((int) filesize($local) / 1048576, 1) . ' Mo';
            return $local;
        }
    }
    @unlink($gz); @unlink($local);

    $log[] = 'ffmpeg statique : installation ÉCHEC (GitHub injoignable depuis le serveur ?).';
    return null;
}

$log = [];

// Poster (image figée avant lecture).
$pdata = grab($POSTER);
if ($pdata !== null) {
    file_put_contents($imgDir . '/hero-poster.png', $pdata);
    $log[] = 'Poster : OK';
}

$log[] = 'Méthode shell : ' . (fn_allowed('shell_exec') ? 'shell_exec' : (fn_allowed('exec') ? 'exec' : (fn_allowed('proc_open') ? 'proc_open' : 'AUCUNE (bloquée)')));

$ffmpeg  = ensure_ffmpeg($log);
$success = false;

if ($ffmpeg) {
    $tmp = sys_get_temp_dir() . '/vhhero_' . substr(md5($out . PHP_VERSION), 0, 8);
    run_cmd('rm -rf ' . escapeshellarg($tmp)); @mkdir($tmp, 0755, true);
    $inputs = '';
    $okAll = true;
    foreach ($CLIPS as $i => $u) {
        if (!grab_to_file($u, "$tmp/c$i.mp4")) { $okAll = false; $log[] = "Clip $i : ÉCHEC"; break; }
        $inputs .= ' -i ' . escapeshellarg("$tmp/c$i.mp4");
        $log[] = "Clip $i : téléchargé (" . round((int) filesize("$tmp/c$i.mp4") / 1048576, 1) . " Mo)";
    }
    if ($okAll) {
        $n = count($CLIPS);
        $fc = '';
        // Chaque plan tronqué à 3 s (montage ~15 s), 720p 24fps, compression web TRÈS
        // légère (CRF 31, plafond 1400 kb/s) → ~2-2,5 Mo, démarrage instantané et fluide.
        for ($i = 0; $i < $n; $i++) { $fc .= "[$i:v]trim=0:3,setpts=PTS-STARTPTS,scale=1280:720:force_original_aspect_ratio=increase,crop=1280:720,setsar=1,fps=24,format=yuv420p[v$i];"; }
        for ($i = 0; $i < $n; $i++) { $fc .= "[v$i]"; }
        $fc .= "concat=n=$n:v=1:a=0[outv]";
        $cmd = escapeshellarg($ffmpeg) . ' -y -loglevel error' . $inputs
            . ' -filter_complex ' . escapeshellarg($fc)
            . ' -map ' . escapeshellarg('[outv]') . ' -an -c:v libx264 -crf 31 -maxrate 1400k -bufsize 2800k'
            . ' -preset veryfast -pix_fmt yuv420p -movflags +faststart '
            . escapeshellarg($out) . ' 2>&1';
        [$res, ] = run_cmd($cmd);
        if (@is_file($out) && filesize($out) > 200000) {
            $success = true;
            $log[] = 'Montage : OK (' . round(filesize($out) / 1048576, 1) . ' Mo)';
            // On retire d'éventuels plans de playlist d'un ancien run.
            foreach (glob($videoDir . '/hero-*.mp4') ?: [] as $f) { @unlink($f); }
            // Poster LÉGER (1re image, ~100 Ko) → fond instantané du hero sous la vidéo.
            $poster = $imgDir . '/hero-poster.jpg';
            run_cmd(escapeshellarg($ffmpeg) . ' -y -loglevel error -i ' . escapeshellarg($out)
                . ' -frames:v 1 -q:v 4 ' . escapeshellarg($poster) . ' 2>&1');
            if (@is_file($poster) && filesize($poster) > 2000) {
                @unlink($imgDir . '/hero-poster.png'); // l'ancien poster lourd n'est plus utile
                $log[] = 'Poster léger : OK (' . round(filesize($poster) / 1024) . ' Ko)';
            }
        } else {
            $log[] = 'Montage : échec ffmpeg → ' . trim((string) $res);
        }
    }
    foreach (glob("$tmp/*.mp4") ?: [] as $f) { @unlink($f); }
    @rmdir($tmp);
} else {
    $log[] = '→ Repli : le site affiche le montage cinématique d\'IMAGES (déjà actif, fluide).';
}

$sizeMo = (@is_file($out) && $success) ? round(filesize($out) / 1048576, 1) : 0;
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Vidéo du hero</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(640px,94vw)">
<h1 style="text-align:center;font-size:1.2rem">Vidéo du hero 🎬</h1>
<div class="alert alert--<?= $success ? 'ok' : 'err' ?>" style="margin:1rem 0">
    <?= $success
        ? '✓ Vraie VIDÉO du montage (5 scènes) recréée et compressée — ' . $sizeMo . ' Mo. Elle démarre toute seule, fluide.'
        : '⚠️ Impossible de générer la vidéo ici → le site utilise le montage animé d\'images (fluide). Détail ci-dessous.' ?>
</div>
<?php if ($success): ?>
<p class="muted">Aperçu en direct de la vidéo générée (elle doit bouger toute seule) :</p>
<video src="<?= e(asset('video/hero.mp4')) ?>" autoplay muted loop playsinline controls
       style="width:100%;border-radius:12px;margin:.4rem 0 1rem;background:#000;aspect-ratio:16/9"></video>
<p class="muted" style="font-size:.82rem">Si elle bouge ici → c'est bon. Recharge ensuite le site en <strong>navigation privée</strong> (Ctrl+Maj+N).</p>
<?php endif; ?>
<pre style="max-height:300px;overflow:auto;background:rgba(0,0,0,.3);padding:.8rem;border-radius:8px;font-size:.74rem;line-height:1.5"><?= e(implode("\n", $log)) ?></pre>
<a class="btn btn--primary" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%">Voir le site →</a>
<p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>make-hero.php</code></strong> une fois la vidéo en place.</p>
</div></div></body></html>
