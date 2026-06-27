<?php
/**
 * ViceHub X — Génération d'articles IA en lot (CLI / cron).
 * Même moteur que le dashboard admin (includes/ai.php), sans limite de temps web.
 *
 * Prérequis : clé Anthropic (env ANTHROPIC_API_KEY ou réglage 'anthropic_key').
 *
 * Exemples :
 *   php scripts/gen-ai-articles.php --count=20 --status=draft
 *   php scripts/gen-ai-articles.php --count=5  --status=published
 *
 * CRON (3 brouillons / jour à relire) :
 *   30 6 * * *  cd /chemin/ViceHubx && php scripts/gen-ai-articles.php --count=3 >/dev/null 2>&1
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ai.php';

$opts   = getopt('', ['count::', 'status::']);
$count  = max(1, (int) ($opts['count'] ?? 5));
$status = in_array($opts['status'] ?? '', ['draft', 'published', 'pending'], true) ? $opts['status'] : 'draft';

if (!ai_enabled()) {
    fwrite(STDERR, "✗ Clé API Anthropic manquante (export ANTHROPIC_API_KEY=\"sk-ant-...\" ou réglage 'anthropic_key').\n");
    exit(1);
}

@set_time_limit(0);
echo "Génération de {$count} article(s) IA (statut : {$status})…\n";
$ok = 0; $dup = 0; $fail = 0;
for ($i = 1; $i <= $count; $i++) {
    try {
        $art = ai_generate_article();
        $id  = ai_save_article($art, $status);
        if ($id) {
            $ok++;
            echo "  ✓ [{$i}/{$count}] #{$id} — {$art['title']}\n";
            echo "       🖼️ image : {$art['image']}  |  prompt OFF : " . mb_substr($art['image_prompt'], 0, 70) . "…\n";
        } else {
            $dup++;
            echo "  ~ [{$i}/{$count}] doublon ignoré — {$art['title']}\n";
        }
    } catch (Throwable $e) {
        $fail++;
        fwrite(STDERR, "  ✗ [{$i}/{$count}] échec : " . $e->getMessage() . "\n");
    }
    usleep(300000);
}
echo "✓ Terminé : {$ok} créé(s), {$dup} doublon(s), {$fail} échec(s).\n";
