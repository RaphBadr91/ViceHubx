<?php
/**
 * ViceHub X — Rapatrie EN LOCAL les portraits IA des PERSONNAGES (Higgsfield).
 *
 * Les fiches personnages (pages/characters.php) affichent un portrait généré par IA.
 * Par défaut ils sont servis depuis le CDN Higgsfield ; ce script les télécharge en
 * LOCAL (/public/assets/img/characters/<slug>.webp) pour un chargement plus rapide et
 * une possession totale des fichiers. character_image() préfère alors le fichier local.
 *
 * À ouvrir UNE fois : https://vicehubx.com/fetch-character-images.php → puis SUPPRIMER.
 * (Fonctionne aussi en CLI : php fetch-character-images.php). Idempotent.
 */
require_once __DIR__ . '/config/config.php';
setup_guard(); // 🔒 accès réservé (admin connecté ou VICEHUB_SETUP=1)

@set_time_limit(0);
@ignore_user_abort(true);

$isCli = (PHP_SAPI === 'cli');

// slug → URL CDN (doit rester aligné avec character_image() dans functions.php).
$map = [
    'lucia'      => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210417_41c8cad8-fec8-4617-8a6c-3a745685e205_min.webp',
    'jason'      => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210420_216ce6c0-7dd9-4c3a-92c4-e2115461bb11_min.webp',
    'le-maire'   => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210421_0c3c9be9-cf79-491f-9dc9-efaf672aa214_min.webp',
    'dj-solaris' => 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260710_210423_d3a12ac6-212d-49bd-9692-a9859d2db0f7_min.webp',
];

$dir = ROOT_PATH . '/public/assets/img/characters';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

function ch_fetch(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_USERAGENT => 'ViceHubX-Chars/1.0',
        ]);
        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($data !== false && $code < 400 && strlen($data) > 500) ? $data : null;
    }
    $data = @file_get_contents($url);
    return ($data !== false && strlen($data) > 500) ? $data : null;
}

$out = [];
foreach ($map as $slug => $url) {
    $dest = $dir . '/' . $slug . '.webp';
    if (is_file($dest)) { $out[] = "= {$slug} : déjà présent"; continue; }
    $bytes = ch_fetch($url);
    if ($bytes === null) { $out[] = "✗ {$slug} : échec du téléchargement"; continue; }
    $out[] = @file_put_contents($dest, $bytes) ? "✓ {$slug} : rapatrié en local" : "✗ {$slug} : écriture impossible";
}

$msg = "Portraits personnages :\n- " . implode("\n- ", $out) . "\n\nTerminé. Tu peux SUPPRIMER ce fichier.";
if ($isCli) {
    echo $msg . "\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
}
