<?php
/**
 * ViceHub X — Téléchargement groupé des vidéos GTA6 en un seul ZIP.
 * Le serveur va chercher chaque vidéo sur le CDN Higgsfield (téléchargement
 * parallèle) et les empaquette. Réservé à un administrateur connecté.
 *   Usage : /admin/download-videos.php  (bouton dans le TikTok Studio)
 */
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in() || !is_admin()) { http_response_code(403); exit('Accès réservé à l\'administration.'); }
@set_time_limit(0);
@ignore_user_abort(true);

$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/';

// Vidéos du calendrier (nom de fichier => fichier CDN), dans l'ordre des jours.
$videos = [
    'jour-01-plage-golden-hour'   => 'hf_20260718_085616_183ba5d7-27e5-46d3-8678-57aaa5dcfe7d.mp4',
    'jour-02-drone-skyline'       => 'hf_20260719_095817_9d4f49a5-cf5d-496b-9e78-192f40763b46.mp4',
    'jour-03-muscle-car-burnout'  => 'hf_20260718_084654_8c879172-b4db-4915-8352-7bb61efe28db.mp4',
    'jour-04-speedboat-sunset'    => 'hf_20260719_065318_9ba86617-71f4-4fac-9c3e-e6d61eae3f34.mp4',
    'jour-05-nightclub-lowrider'  => 'hf_20260719_065336_81e34982-ea59-4917-859a-657fcfb92dd6.mp4',
    'jour-06-helicoptere-baie'    => 'hf_20260719_095812_5f7db3d4-e18d-4348-a0b3-509d4b62f686.mp4',
    'jour-07-course-poursuite'    => 'hf_20260719_095814_c12afc57-6946-43ef-85c2-dd774306eda1.mp4',
    'jour-08-perso-boardwalk'     => 'hf_20260719_095827_170dc484-ad15-4d32-80d2-759c725149e4.mp4',
    'jour-09-moto-nuit'           => 'hf_20260719_095830_6beafd16-9dce-4e17-b839-513786315cca.mp4',
    'jour-10-arcade-retro'        => 'hf_20260719_095833_296d036f-1cfa-4ec8-ac02-c1e98a92b4ec.mp4',
    'jour-11-marina-yachts'       => 'hf_20260719_171110_a502ec7e-d43a-4089-9a1f-fc63b656fca2.mp4',
    'jour-12-rue-neon-pluie'      => 'hf_20260719_171113_c9615bd1-163b-42d2-92ce-ec746fbded45.mp4',
    'jour-13-highway-desert'      => 'hf_20260719_171115_07bfff30-059d-4bd6-a0c1-5e298f4c94ff.mp4',
    'jour-14-rooftop-pool'        => 'hf_20260719_171127_e350378b-2738-46d4-9f6f-707788a1defe.mp4',
    'jour-15-cabriolet-pont'      => 'hf_20260719_171132_00f15d17-b383-403b-9e92-c499dfa04844.mp4',
    'jour-16-diner-neon'          => 'hf_20260719_171135_b6143253-a25f-4c3a-9f2f-14aa4aba637f.mp4',
    'jour-17-plage-volley'        => 'hf_20260719_171148_e00c4684-cf89-46b4-aed4-d783816ee18d.mp4',
    'jour-18-timelapse-trafic'    => 'hf_20260719_171151_a81641ed-f607-424e-aec5-0bbdedba314f.mp4',
    'jour-19-fete-foraine'        => 'hf_20260719_171154_14b1ea9d-9a30-4e9b-81e0-57adb34f4c8e.mp4',
    'bonus-01-cabriolet-rose'     => 'hf_20260717_090002_08585bf0-c9c4-4ec8-a9c1-f3655df21ec9.mp4',
    'bonus-02-downtown-nuit'      => 'hf_20260717_185040_09d8acee-22b8-493a-8fdf-ca864daf26f3.mp4',
];

// Dossier de travail (dans /uploads, garanti accessible en écriture).
$work = UPLOAD_DIR . '/_ziptmp';
if (!is_dir($work)) { @mkdir($work, 0775, true); }
if (!is_writable($work)) { http_response_code(500); exit('Dossier temporaire non accessible en écriture : ' . $work); }

// --- Téléchargement PARALLÈLE (curl_multi) : rapide, évite le timeout ---
$mh = curl_multi_init();
$jobs = [];
$idx = 0;
foreach ($videos as $name => $file) {
    $local = $work . '/' . $idx . '.mp4';
    $fp = @fopen($local, 'wb');
    if (!$fp) { continue; }
    $ch = curl_init($CDN . $file);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    curl_multi_add_handle($mh, $ch);
    $jobs[] = ['ch' => $ch, 'fp' => $fp, 'local' => $local, 'name' => $name];
    $idx++;
}

do {
    $status = curl_multi_exec($mh, $active);
    if ($active) { curl_multi_select($mh, 1.0); }
} while ($active && $status === CURLM_OK);

foreach ($jobs as $j) { curl_multi_remove_handle($mh, $j['ch']); curl_close($j['ch']); fclose($j['fp']); }
curl_multi_close($mh);

// --- Construction du ZIP ---
if (!class_exists('ZipArchive')) { http_response_code(500); exit('Extension ZIP indisponible sur le serveur.'); }
$zipPath = $work . '/vicehubx-videos.zip';
@unlink($zipPath);
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500); exit('Impossible de créer le ZIP.');
}
$added = 0;
foreach ($jobs as $j) {
    if (is_file($j['local']) && filesize($j['local']) > 1024) {
        $zip->addFile($j['local'], $j['name'] . '.mp4');
        $added++;
    }
}
$zip->close();

if ($added === 0 || !is_file($zipPath)) {
    // Nettoyage
    foreach ($jobs as $j) { @unlink($j['local']); }
    http_response_code(502);
    exit('Aucune vidéo récupérée (le CDN est peut-être injoignable depuis le serveur). Réessaie.');
}

// --- Envoi du ZIP au navigateur ---
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="vicehubx-videos-gta6.zip"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-store');
readfile($zipPath);

// --- Nettoyage ---
foreach ($jobs as $j) { @unlink($j['local']); }
@unlink($zipPath);
@rmdir($work);
