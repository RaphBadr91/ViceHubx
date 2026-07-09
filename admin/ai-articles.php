<?php
/**
 * ViceHub X — Dashboard « Articles IA ».
 * Génère automatiquement des articles dans la niche GTA VI / Vice City via l'IA,
 * illustre chaque article avec la banque d'images IA (CDN), et stocke pour chacun
 * le PROMPT IMAGE (en OFF, admin-only) prêt à coller dans Higgsfield.
 */
require __DIR__ . '/../includes/admin_header.php';
require_once dirname(__DIR__) . '/includes/ai.php';

$flash = null;
$results = [];

$act = $_POST['action'] ?? '';
if ($act !== '' && !verify_csrf()) {
    $flash = ['err', 'Jeton CSRF invalide.'];
    $act = '';
}

// --- Connexion de la clé IA ---
if ($act === 'save_key') {
    $k = trim((string) ($_POST['anthropic_key'] ?? ''));
    if ($k !== '') { set_setting('anthropic_key', $k); } // vide = on conserve l'existante
    set_setting('ai_model', trim((string) ($_POST['ai_model'] ?? '')));
    $flash = ['ok', 'Réglages IA enregistrés.'];
}

// --- Publication automatique (réglages) ---
if ($act === 'save_auto') {
    set_setting('ai_auto_enabled', !empty($_POST['ai_auto_enabled']) ? '1' : '0');
    $iv = (int) ($_POST['ai_auto_interval'] ?? 6);
    set_setting('ai_auto_interval', (string) (in_array($iv, [3, 5, 7, 10, 12], true) ? $iv : 6));
    $bt = (int) ($_POST['ai_auto_batch'] ?? 10);
    set_setting('ai_auto_batch', (string) (in_array($bt, [5, 10, 15, 20], true) ? $bt : 10));
    set_setting('ai_auto_status', ($_POST['ai_auto_status'] ?? 'published') === 'draft' ? 'draft' : 'published');
    $flash = ['ok', '✅ Publication automatique enregistrée.'];
}
// --- Test manuel du déclencheur auto (génère le lot dû maintenant) ---
if ($act === 'run_auto') {
    @set_time_limit(0);
    $r = ai_auto_run(110);
    $flash = [$r['generated'] > 0 ? 'ok' : 'err', $r['message']];
}

// --- Génération en lot ---
if ($act === 'generate') {
    $count  = (int) ($_POST['count'] ?? 0);
    $count  = in_array($count, [5, 10, 15, 20], true) ? $count : 5;
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
    if (!ai_enabled()) {
        $flash = ['err', 'Connecte d’abord ta clé API Anthropic ci-dessous.'];
    } else {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $ok = 0; $dup = 0; $fail = 0;
        for ($i = 0; $i < $count; $i++) {
            try {
                $art = ai_generate_article();
                $id  = ai_save_article($art, $status, (int) ($admin_user['id'] ?? 0) ?: null);
                if ($id) { $results[] = ['id' => $id] + $art; $ok++; }
                else { $dup++; }
            } catch (Throwable $e) {
                $fail++;
                $results[] = ['error' => $e->getMessage()];
            }
        }
        $flash = ['ok', "Génération terminée : {$ok} article(s) créé(s)" . ($dup ? ", {$dup} doublon(s) ignoré(s)" : '') . ($fail ? ", {$fail} échec(s)" : '') . '.'];
    }
}

// --- Publier / supprimer un article IA ---
if ($act === 'publish') {
    db()->prepare("UPDATE articles SET status='published', published_at=COALESCE(published_at, NOW()) WHERE id=?")
        ->execute([(int) ($_POST['id'] ?? 0)]);
    $flash = ['ok', 'Article publié.'];
}
if ($act === 'delete') {
    db()->prepare('DELETE FROM articles WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
    $flash = ['ok', 'Article supprimé.'];
}

// Derniers articles générés par IA (ceux qui ont un prompt image stocké).
$aiArticles = db()->query(
    "SELECT id, title, slug, status, image, image_prompt, created_at
     FROM articles WHERE image_prompt IS NOT NULL AND image_prompt <> ''
     ORDER BY id DESC LIMIT 40"
)->fetchAll();

$enabled = ai_enabled();

// Clé du déclencheur auto (auto-générée une fois) + réglages courants.
$tickKey = (string) get_setting('ai_tick_key', '');
if ($tickKey === '') { $tickKey = bin2hex(random_bytes(16)); set_setting('ai_tick_key', $tickKey); }
$autoEnabled  = (int) get_setting('ai_auto_enabled', '0') === 1;
$autoInterval = (int) get_setting('ai_auto_interval', '6');
$autoBatch    = (int) get_setting('ai_auto_batch', '10');
$autoStatus   = get_setting('ai_auto_status', 'published');
$autoLast     = (int) get_setting('ai_auto_last', '0');
$autoPending  = (int) get_setting('ai_auto_pending', '0');
$tickUrl      = rtrim(site_base_url(), '/') . '/ai-tick.php?key=' . $tickKey;
?>
<section class="section">
    <span class="eyebrow">🤖 Automatisation</span>
    <h1>Articles IA — Génération automatique</h1>
    <p class="muted">Crée des articles professionnels dans la niche GTA VI / Vice City, illustrés depuis la banque d’images IA. Chaque article embarque un <strong>prompt image</strong> (en OFF) prêt pour Higgsfield.</p>

    <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash[0]) ?>" style="margin:1rem 0"><?= e($flash[1]) ?></div>
    <?php endif; ?>

    <!-- État IA -->
    <div class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:1rem 0;display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
        <span style="font-size:1.4rem"><?= $enabled ? '🟢' : '🔴' ?></span>
        <div>
            <strong>IA <?= $enabled ? 'connectée' : 'non connectée' ?></strong>
            <p class="muted" style="margin:.2rem 0 0">Modèle : <code><?= e(ai_model()) ?></code><?= $enabled ? '' : ' — ajoute ta clé Anthropic ci-dessous pour activer la génération.' ?></p>
        </div>
    </div>

    <!-- Génération -->
    <div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0">
        <h2 style="margin-top:0">⚡ Générer des articles</h2>
        <form method="post" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generate">
            <label>Statut
                <select name="status" style="display:block;margin-top:.3rem">
                    <option value="draft">Brouillon (à relire)</option>
                    <option value="published">Publier directement</option>
                </select>
            </label>
            <div>
                <span class="muted" style="display:block;margin-bottom:.3rem">Nombre d’articles</span>
                <div style="display:flex;gap:.5rem">
                    <?php foreach ([5, 10, 15, 20] as $n): ?>
                        <button class="btn btn--primary" type="submit" name="count" value="<?= $n ?>" <?= $enabled ? '' : 'disabled' ?>><?= $n ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
        <p class="muted" style="font-size:.82rem;margin:.8rem 0 0">💡 Articles longs (~2000 mots, tons variés). En manuel, reste sur <strong>5</strong> à la fois (10-20 peuvent dépasser le délai). Pour du volume, utilise la <strong>publication automatique</strong> ci-dessous (sans limite, reprise automatique).</p>
    </div>

    <!-- PUBLICATION AUTOMATIQUE -->
    <div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0;border:1px solid rgba(255,46,136,.25)">
        <h2 style="margin-top:0">🔁 Publication AUTOMATIQUE (pour grimper sur Google)</h2>
        <p class="muted">Le site écrit et publie tout seul <strong>1 article complet</strong> (~2000 mots, 5 tons : journaliste, joueur, connaisseur, passionné, geek) <strong>à chaque intervalle</strong> — avec FAQ intégrée pour l'AI Overview de Google. Un rythme régulier que Google adore.</p>

        <form method="post" style="display:flex;gap:1.2rem;flex-wrap:wrap;align-items:flex-end;margin-top:.6rem">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_auto">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="ai_auto_enabled" value="1" style="width:auto" <?= $autoEnabled ? 'checked' : '' ?>> <strong>Activer</strong>
            </label>
            <label>1 article
                <select name="ai_auto_interval" style="display:block;margin-top:.3rem">
                    <?php foreach ([3, 5, 7, 10, 12] as $h): ?><option value="<?= $h ?>" <?= $autoInterval === $h ? 'selected' : '' ?>>toutes les <?= $h ?> h</option><?php endforeach; ?>
                </select>
            </label>
            <label>Statut
                <select name="ai_auto_status" style="display:block;margin-top:.3rem">
                    <option value="published" <?= $autoStatus === 'published' ? 'selected' : '' ?>>Publier directement</option>
                    <option value="draft" <?= $autoStatus === 'draft' ? 'selected' : '' ?>>Brouillon (à relire)</option>
                </select>
            </label>
            <button class="btn btn--primary" type="submit">Enregistrer</button>
        </form>

        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--glass-brd)">
            <p style="margin:0 0 .4rem"><strong>État :</strong>
                <?= $autoEnabled ? '🟢 Activée' : '⚪ Désactivée' ?> ·
                <?= $autoEnabled ? "1 article toutes les {$autoInterval} h (soit ~" . round(24 / $autoInterval, 1) . "/jour)" : 'en pause' ?>
                <?php if ($autoLast): ?> · dernier article : <?= e(date('d/m/Y H:i', $autoLast)) ?><?php endif; ?>
            </p>
            <p class="muted" style="font-size:.86rem;margin:.6rem 0 .3rem">Pour que ça tourne 24h/24, branche ce lien sur un cron (toutes les 30 min) — <a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> (gratuit) ou cPanel → Cron Jobs :</p>
            <input type="text" readonly value="<?= e($tickUrl) ?>" onclick="this.select()" style="width:100%;font-size:.8rem;font-family:monospace;padding:.5rem;border-radius:8px;background:rgba(255,255,255,.05);color:#cfc9dd;border:1px solid var(--glass-brd)">
            <div style="display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap">
                <button class="btn btn--ghost" type="button" data-copy="<?= e($tickUrl) ?>">📋 Copier le lien cron</button>
                <form method="post" style="display:inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="run_auto">
                    <button class="btn btn--ghost" type="submit" <?= $enabled ? '' : 'disabled' ?>>▶️ Tester maintenant</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Connexion clé -->
    <details class="glass" style="padding:1rem 1.2rem;border-radius:14px;margin:1rem 0"<?= $enabled ? '' : ' open' ?>>
        <summary style="cursor:pointer;font-weight:700">🔑 Connecter / changer la clé IA (Anthropic)</summary>
        <form method="post" style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_key">
            <label style="flex:1;min-width:280px">Clé API Anthropic
                <input type="password" name="anthropic_key" placeholder="sk-ant-..." autocomplete="off" style="display:block;width:100%;margin-top:.3rem" value="<?= e(get_setting('anthropic_key', '') ? '' : '') ?>">
                <small class="muted">Laisse vide pour conserver la clé actuelle si déjà saisie. Tu peux aussi définir la variable d’environnement <code>ANTHROPIC_API_KEY</code>.</small>
            </label>
            <label>Modèle
                <input type="text" name="ai_model" placeholder="claude-haiku-4-5-20251001" value="<?= e(get_setting('ai_model', '')) ?>" style="display:block;margin-top:.3rem">
            </label>
            <button class="btn btn--ghost" type="submit">Enregistrer</button>
        </form>
    </details>

    <!-- Résultats de la dernière génération -->
    <?php if ($results): ?>
    <div class="glass" style="padding:1.2rem;border-radius:14px;margin:1rem 0">
        <h2 style="margin-top:0">✅ Dernière génération</h2>
        <ul style="line-height:1.9">
            <?php foreach ($results as $r): ?>
                <?php if (isset($r['error'])): ?>
                    <li style="color:#ff6b6b">⚠️ Échec : <?= e($r['error']) ?></li>
                <?php else: ?>
                    <li>📝 <a href="<?= e(url('admin/article-edit.php?id=' . (int) $r['id'])) ?>"><?= e($r['title']) ?></a> <span class="muted">(<?= e($r['category']) ?>)</span></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Banque : articles IA + prompts image (OFF) -->
    <h2 style="margin-top:2rem">🖼️ Articles IA &amp; prompts image (OFF, pour Higgsfield)</h2>
    <p class="muted">Le prompt ci-dessous n’est jamais affiché aux visiteurs. Copie-le dans Higgsfield pour générer une illustration sur-mesure, puis remplace l’image de l’article.</p>

    <?php if (!$aiArticles): ?>
        <p class="muted">Aucun article IA pour l’instant. Lance une génération ci-dessus.</p>
    <?php else: ?>
        <div style="display:grid;gap:1rem;margin-top:1rem">
            <?php foreach ($aiArticles as $a): ?>
                <div class="glass" style="padding:1rem 1.2rem;border-radius:14px;display:grid;grid-template-columns:90px 1fr;gap:1rem;align-items:start">
                    <div>
                        <?php if (!empty($a['image'])): ?>
                            <img src="<?= e(img_src($a['image'])) ?>" alt="" loading="lazy" style="width:90px;height:64px;object-fit:cover;border-radius:8px" onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0">
                        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
                            <span class="chip"><?= e($a['status']) ?></span>
                            <strong><?= e($a['title']) ?></strong>
                        </div>
                        <label class="muted" style="display:block;margin:.6rem 0 .2rem;font-size:.8rem">Prompt image (OFF) — Higgsfield</label>
                        <textarea readonly rows="2" style="width:100%;font-size:.82rem;background:rgba(255,255,255,.04);color:#cfc9dd;border-radius:8px;border:1px solid rgba(255,255,255,.1);padding:.5rem" onclick="this.select()"><?= e($a['image_prompt']) ?></textarea>
                        <div style="display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap">
                            <button class="btn btn--ghost" type="button" data-copy="<?= e($a['image_prompt']) ?>">📋 Copier le prompt</button>
                            <a class="btn btn--ghost" href="<?= e(url('admin/article-edit.php?id=' . (int) $a['id'])) ?>">✏️ Éditer</a>
                            <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>" target="_blank">👁️ Voir</a>
                            <?php if ($a['status'] !== 'published'): ?>
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?><input type="hidden" name="action" value="publish"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="btn btn--primary" type="submit">🚀 Publier</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cet article ?')">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="btn btn--ghost" type="submit" style="color:#ff6b6b">🗑️</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
        navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
            var t = b.textContent; b.textContent = '✅ Copié !';
            setTimeout(function () { b.textContent = t; }, 1500);
        });
    });
});
</script>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
