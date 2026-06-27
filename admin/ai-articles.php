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
    set_setting('anthropic_key', trim((string) ($_POST['anthropic_key'] ?? '')));
    set_setting('ai_model', trim((string) ($_POST['ai_model'] ?? '')));
    $flash = ['ok', 'Réglages IA enregistrés.'];
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
        <p class="muted" style="font-size:.82rem;margin:.8rem 0 0">💡 Pour 15-20 articles, la génération peut prendre 1 à 2 min. Tu peux aussi lancer en arrière-plan : <code>php scripts/gen-ai-articles.php --count=20 --status=draft</code>.</p>
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
