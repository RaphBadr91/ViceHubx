<?php
/**
 * ViceHub X — Téléchargement groupé des vidéos GTA6 (ZIP).
 *
 * Empaqueter 21 vidéos (~100 Mo) en une seule requête dépasse le temps limite
 * d'un hébergement mutualisé (page qui « bug »). On procède donc en 2 temps :
 *   1) « Préparer » → le serveur télécharge + compresse EN ARRIÈRE-PLAN
 *      (réponse renvoyée d'abord ; aucun timeout).
 *   2) « Télécharger » → un simple fichier STATIQUE servi par le serveur (rapide).
 * Réservé à un administrateur connecté.
 */
require_once dirname(__DIR__) . '/config/config.php';
if (!is_logged_in() || !is_admin()) { http_response_code(403); exit('Accès réservé à l\'administration.'); }
@set_time_limit(0);
@ignore_user_abort(true);

$CDN = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/';

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

$dlDir   = UPLOAD_DIR . '/dl';
if (!is_dir($dlDir)) { @mkdir($dlDir, 0775, true); }
$zipName = 'vicehubx-videos-gta6.zip';
$zipPath = $dlDir . '/' . $zipName;
$zipUrl  = '/uploads/dl/' . $zipName;   // servi en statique par le serveur

$canDetach = function_exists('litespeed_finish_request') || function_exists('fastcgi_finish_request');

/* ------------------------------------------------------------------ */
/*  ACTION : préparer le ZIP en arrière-plan                           */
/* ------------------------------------------------------------------ */
if (isset($_GET['build'])) {
    set_setting('vhx_zip_ts', (string) time());        // repère « préparation lancée »
    @unlink($zipPath);                                  // l'ancien disparaît → statut « en cours »

    $builder = static function () use ($CDN, $videos, $dlDir, $zipPath) {
        @set_time_limit(0); @ignore_user_abort(true);
        // 1) Téléchargement parallèle
        $mh = curl_multi_init(); $jobs = []; $i = 0;
        foreach ($videos as $name => $file) {
            $local = $dlDir . '/tmp_' . $i . '.mp4';
            $fp = @fopen($local, 'wb');
            if (!$fp) { continue; }
            $ch = curl_init($CDN . $file);
            curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 20]);
            curl_multi_add_handle($mh, $ch);
            $jobs[] = ['ch' => $ch, 'fp' => $fp, 'local' => $local, 'name' => $name];
            $i++;
        }
        do { $s = curl_multi_exec($mh, $act); if ($act) { curl_multi_select($mh, 1.0); } } while ($act && $s === CURLM_OK);
        foreach ($jobs as $j) { curl_multi_remove_handle($mh, $j['ch']); curl_close($j['ch']); fclose($j['fp']); }
        curl_multi_close($mh);
        // 2) ZIP (dans un fichier temporaire, renommé à la fin = atomique)
        if (class_exists('ZipArchive')) {
            $part = $zipPath . '.part';
            @unlink($part);
            $zip = new ZipArchive();
            if ($zip->open($part, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($jobs as $j) {
                    if (is_file($j['local']) && filesize($j['local']) > 1024) { $zip->addFile($j['local'], $j['name'] . '.mp4'); }
                }
                $zip->close();
                if (is_file($part) && filesize($part) > 1024) { @rename($part, $zipPath); }
            }
        }
        foreach ($jobs as $j) { @unlink($j['local']); }
    };

    if ($canDetach) {
        register_shutdown_function(static function () use ($builder) {
            if (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
            elseif (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
            $builder();
        });
    } else {
        // Pas de détachement possible : on construit en direct (peut être long).
        $builder();
    }
}

/* ------------------------------------------------------------------ */
/*  RENDU de la mini-page                                              */
/* ------------------------------------------------------------------ */
$exists   = is_file($zipPath) && filesize($zipPath) > 1024;
$sizeMb   = $exists ? round(filesize($zipPath) / 1048576, 1) : 0;
$since    = time() - (int) get_setting('vhx_zip_ts', '0');
$building = isset($_GET['build']) || (!$exists && $since < 300);

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Télécharger les vidéos — ViceHub X</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Arial;background:#0f0d1a;color:#eee;max-width:640px;margin:8vh auto;padding:24px;line-height:1.5}
  h1{font-size:1.5rem}
  a.btn{display:inline-block;background:linear-gradient(90deg,#ff2d78,#a12dff);color:#fff;padding:.75rem 1.3rem;border-radius:12px;text-decoration:none;font-weight:800;margin:.5rem .5rem 0 0}
  a.ghost{background:#241f38;color:#cfc9dd}
  .muted{color:#9a93b0;font-size:.9rem}
  .box{background:#181528;border:1px solid #2a2740;border-radius:14px;padding:1.2rem 1.4rem}
</style></head><body>
<h1>⬇️ Vidéos GTA6 — ZIP</h1>
<div class="box">
<?php if ($exists): ?>
  <p>✅ <strong>Ton ZIP est prêt !</strong> (<?= $sizeMb ?> Mo · 21 vidéos)</p>
  <a class="btn" href="<?= e($zipUrl) ?>?t=<?= time() ?>" download>⬇️ Télécharger le ZIP</a>
  <a class="btn ghost" href="?build=1">🔁 Régénérer</a>
<?php elseif ($building): ?>
  <p>⏳ <strong>Préparation en cours…</strong> le serveur télécharge les 21 vidéos puis les compresse.</p>
  <p class="muted">Attends <strong>~1 minute</strong>, puis clique « Rafraîchir ». Le bouton de téléchargement apparaîtra dès que c'est prêt.</p>
  <a class="btn" href="?">🔄 Rafraîchir</a>
<?php else: ?>
  <p>Prépare l'archive de tes 21 vidéos (jour-01 … jour-19 + 2 bonus).</p>
  <a class="btn" href="?build=1">📦 Préparer le ZIP</a>
<?php endif; ?>
</div>
<p style="margin-top:1.5rem"><a class="muted" href="<?= e(url('admin/tiktok.php')) ?>">← Retour au TikTok Studio</a></p>
</body></html>
