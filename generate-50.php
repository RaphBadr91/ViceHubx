<?php
/**
 * ViceHub X — Lance en 1 clic un LOT d'articles IA « Programmés CRON ».
 *
 * Ouvre simplement (connecté en admin) :
 *   https://vicehubx.com/generate-50.php          → 50 articles
 *   https://vicehubx.com/generate-50.php?n=30     → 30 articles
 *
 * Ce qu'il fait, SANS bloquer le site :
 *   • règle la publication auto sur « 1 article toutes les 3h » ;
 *   • met N articles en file (statut « Programmée CRON », personnalités en rotation) ;
 *   • lance le worker en arrière-plan : chaque article est rédigé (~2000 mots, FAQ,
 *     JSON-LD, faits vérifiés) AVEC son illustration Higgsfield (style Vice City),
 *     puis programmé pour être publié 1 par 1 toutes les 3h par le CRON.
 *
 * Après ça, tu génères tes prochains lots directement depuis l'admin (Articles IA).
 * Sécurisé (admin requis). Idempotent : rappelable pour ajouter d'autres lots.
 */
require_once __DIR__ . '/config/config.php';
setup_guard();                    // 🔒 admin connecté (ou VICEHUB_SETUP=1)
require_once ROOT_PATH . '/includes/ai.php';

header('Content-Type: text/html; charset=utf-8');

$n = (int) ($_GET['n'] ?? 50);
$n = max(1, min(100, $n));        // borne de sécurité

if (!ai_enabled()) {
    exit('<p style="font-family:sans-serif">⛔ Connecte d’abord ta clé API Anthropic dans l’admin (Articles IA) avant de lancer la génération.</p>');
}

// 1) Cadence : 1 article toutes les 3 heures + auto-publication activée (flux continu).
set_setting('ai_auto_interval', '3');
set_setting('ai_auto_enabled', '1');
set_setting('ai_auto_status', 'published');

// 2) File : N articles « Programmée CRON », personnalités en rotation (Multi).
ai_queue_add($n, 'pending', 'multi');

// 3) Worker détaché : il rédige + illustre les articles en arrière-plan.
$spawned = ai_spawn_worker('ai-worker.php');

$imgOn = ai_img_enabled();
$queue = (int) get_setting('ai_gen_queue', '0');
?>
<!doctype html><meta charset="utf-8">
<div style="font-family:system-ui,sans-serif;max-width:640px;margin:3rem auto;line-height:1.6;color:#1a1a2e">
  <h1 style="color:#ff2e88">🚀 C’est parti !</h1>
  <p><strong><?= (int) $n ?> articles</strong> viennent d’être mis en file, en <strong>« Programmée CRON »</strong>, personnalités en rotation.</p>
  <ul>
    <li>⏱️ Cadence réglée : <strong>1 article publié toutes les 3&nbsp;h</strong> (auto activée).</li>
    <li>🖼️ Illustrations Higgsfield : <?= $imgOn ? '<strong>activées</strong> (chaque article aura son image)' : '<em>désactivées</em> — active ta clé Higgsfield pour des images sur-mesure (sinon banque d’images)' ?>.</li>
    <li>⚙️ Worker en arrière-plan : <?= $spawned ? '<strong>lancé</strong>' : 'non lancé (le CRON prendra le relais)' ?>. File actuelle : <strong><?= $queue ?></strong>.</li>
  </ul>
  <p>La rédaction + les images se font <strong>en arrière-plan</strong> (~1&nbsp;min/article). Tu peux fermer cette page. Suis la progression dans l’admin → <strong>Articles IA</strong> (barre de %) et → <strong>Articles</strong> (statuts colorés + dates prévues).</p>
  <p style="background:#fff3cd;padding:.8rem 1rem;border-radius:10px;border:1px solid #ffe08a">
    ⚠️ <strong>Important :</strong> vérifie que ton <strong>CRON</strong> est bien branché (toutes les 30&nbsp;min) sur
    <code>ai-tick.php</code> — c’est lui qui génère la file ET publie 1 article toutes les 3&nbsp;h.
    Sans lui, les articles restent « programmés » sans jamais se publier.
  </p>
  <p style="color:#666;font-size:.9rem">🔒 Pense à <strong>supprimer ce fichier</strong> (<code>generate-50.php</code>) quand tu as fini, ou garde-le pour relancer des lots. Pour tes prochains lots, utilise directement l’admin → Articles IA.</p>
  <p><a href="<?= e(url('admin/ai-articles.php')) ?>" style="display:inline-block;margin-top:.5rem;background:linear-gradient(90deg,#ff2e88,#7a5cff);color:#fff;padding:.7rem 1.2rem;border-radius:10px;text-decoration:none;font-weight:700">→ Voir la progression dans l’admin</a></p>
</div>
