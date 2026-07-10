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

// --- Connexion de la clé Higgsfield (illustrations IA) ---
if ($act === 'save_image') {
    $hk = trim((string) ($_POST['higgsfield_key'] ?? ''));
    if ($hk !== '') { set_setting('higgsfield_key', $hk); } // vide = on conserve l'existante
    $ep = trim((string) ($_POST['ai_image_endpoint'] ?? ''));
    set_setting('ai_image_endpoint', $ep);
    if (isset($_POST['ai_image_resolution'])) {
        set_setting('ai_image_resolution', trim((string) $_POST['ai_image_resolution']));
    }
    set_setting('ai_image_enabled', !empty($_POST['ai_image_enabled']) ? '1' : '0');
    $flash = ['ok', '🎨 Réglages illustrations IA enregistrés.'];
}

// --- Publication automatique (réglages) ---
if ($act === 'save_auto') {
    set_setting('ai_auto_enabled', !empty($_POST['ai_auto_enabled']) ? '1' : '0');
    $iv = (int) ($_POST['ai_auto_interval'] ?? 6);
    set_setting('ai_auto_interval', (string) (in_array($iv, [3, 5, 7, 10, 12], true) ? $iv : 6));
    $bt = (int) ($_POST['ai_auto_batch'] ?? 10);
    set_setting('ai_auto_batch', (string) (in_array($bt, [5, 10, 15, 20], true) ? $bt : 10));
    set_setting('ai_auto_status', ($_POST['ai_auto_status'] ?? 'published') === 'draft' ? 'draft' : 'published');
    $atone = (string) ($_POST['ai_auto_tone'] ?? 'multi');
    set_setting('ai_auto_tone', ($atone === 'multi' || in_array($atone, array_keys(ai_tones()), true)) ? $atone : 'multi');
    $flash = ['ok', '✅ Publication automatique enregistrée.'];
}
// --- Test manuel du déclencheur auto (génère le lot dû maintenant) ---
if ($act === 'run_auto') {
    @set_time_limit(0);
    $r = ai_auto_run(110);
    $flash = [$r['generated'] > 0 ? 'ok' : 'err', $r['message']];
}

// --- Génération en lot (EN ARRIÈRE-PLAN : n'immobilise jamais le site) ---
if ($act === 'generate') {
    $count  = (int) ($_POST['count'] ?? 0);
    $count  = in_array($count, [5, 10, 15, 20], true) ? $count : 5;
    $status = in_array($_POST['status'] ?? '', ['draft', 'pending', 'published'], true) ? $_POST['status'] : 'draft';
    $tone   = (string) ($_POST['tone'] ?? 'multi');
    if (!ai_enabled()) {
        $flash = ['err', 'Connecte d’abord ta clé API Anthropic ci-dessous.'];
    } else {
        ai_queue_add($count, $status, $tone);   // met en file (statut + personnalité)
        $spawned = ai_spawn_worker();           // lance le worker détaché (arrière-plan)
        $lbl = ['draft' => 'en brouillon', 'pending' => 'programmé(s) (CRON)', 'published' => 'à publier'][$status] ?? '';
        $flash = ['ok', "🚀 {$count} article(s) {$lbl} en génération EN ARRIÈRE-PLAN. "
            . "Le site reste fluide — ils apparaissent au fil de l'eau (recharge cette page dans quelques minutes)."
            . ($spawned ? '' : ' ⚠️ Lancement direct indisponible : c\'est le CRON qui les générera.')];
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
// --- (Re)générer l'illustration IA (Higgsfield) d'un article ---
if ($act === 'gen_image') {
    @set_time_limit(0);
    $id  = (int) ($_POST['id'] ?? 0);
    $row = db()->prepare('SELECT slug, image_prompt FROM articles WHERE id=?');
    $row->execute([$id]);
    $art = $row->fetch();
    if (!$art || trim((string) $art['image_prompt']) === '') {
        $flash = ['err', 'Aucun prompt image pour cet article.'];
    } elseif (!ai_img_enabled()) {
        $flash = ['err', 'Active d’abord les illustrations IA (clé Higgsfield) ci-dessous.'];
    } else {
        $path = ai_generate_image((string) $art['image_prompt'], (string) $art['slug']);
        if ($path) {
            db()->prepare('UPDATE articles SET image=? WHERE id=?')->execute([$path, $id]);
            $flash = ['ok', '🎨 Illustration générée via Higgsfield.'];
        } else {
            $flash = ['err', 'Échec de la génération d’image (clé/endpoint ou quota Higgsfield ?).'];
        }
    }
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

// Progression de publication des articles programmés (barre de %).
$prog = ai_sched_progress();

// Réglages illustrations IA (Higgsfield).
$imgEnabled  = ai_img_enabled();
$imgOn       = (int) get_setting('ai_image_enabled', '0') === 1;
$hasImgKey   = ai_img_key() !== '';
$imgEndpoint = (string) get_setting('ai_image_endpoint', '');
$imgRes      = (string) get_setting('ai_image_resolution', '');
?>
<section class="section">
    <span class="eyebrow">🤖 Automatisation</span>
    <h1>Articles IA — Génération automatique</h1>
    <p class="muted">Crée des articles professionnels dans la niche GTA VI / Vice City (~2000 mots, FAQ + JSON-LD). Chaque article reçoit une <strong>illustration IA sur-mesure générée par Higgsfield</strong> à la création (si activée ci-dessous), sinon une image de la banque.</p>

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
                    <option value="draft">📝 Brouillon (à relire)</option>
                    <option value="pending">⏱️ Programmée CRON (publiée 1 par 1)</option>
                    <option value="published">🚀 Publiée (tout de suite)</option>
                </select>
            </label>
            <label>Personnalité (ton)
                <select name="tone" style="display:block;margin-top:.3rem">
                    <option value="multi">🎲 Multi — change à chaque article</option>
                    <?php foreach (ai_tone_labels() as $tk => $tl): ?>
                        <option value="<?= e($tk) ?>"><?= e($tl) ?></option>
                    <?php endforeach; ?>
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
        <?php $queue = (int) get_setting('ai_gen_queue', '0'); ?>
        <?php if ($queue > 0): ?>
            <div class="alert alert--ok" style="margin:.9rem 0 0">⏳ <strong><?= $queue ?></strong> article(s) en cours de génération en arrière-plan… (recharge la page pour suivre)</div>
        <?php endif; ?>
        <p class="muted" style="font-size:.82rem;margin:.8rem 0 0">💡 Articles longs (~2000 mots), <strong>vérifiés</strong> (faits confirmés vs rumeurs clairement distingués). La génération tourne <strong>en arrière-plan</strong> : le site reste fluide, tu peux fermer la page.<br>
            🎭 <strong>Personnalité</strong> : choisis l'une des 5 (📰 Journaliste, 🎮 Joueur, 🎓 Connaisseur, ❤️ Passionné, 🤓 Geek) pour tout le lot, ou <strong>🎲 Multi</strong> = chaque article prend une personnalité différente (rotation), pour toucher un max de fans.<br>
            Statuts : <strong>Brouillon</strong> = à relire · <strong>Programmée CRON</strong> = publiés <strong>1 par 1</strong> selon l'intervalle · <strong>Publiée</strong> = en ligne tout de suite.</p>
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
            <?php $autoTone = (string) get_setting('ai_auto_tone', 'multi'); ?>
            <label>Personnalité
                <select name="ai_auto_tone" style="display:block;margin-top:.3rem">
                    <option value="multi" <?= $autoTone === 'multi' ? 'selected' : '' ?>>🎲 Multi (rotation)</option>
                    <?php foreach (ai_tone_labels() as $tk => $tl): ?>
                        <option value="<?= e($tk) ?>" <?= $autoTone === $tk ? 'selected' : '' ?>><?= e($tl) ?></option>
                    <?php endforeach; ?>
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

            <!-- Barre de progression : publication des articles PROGRAMMÉS (CRON) -->
            <?php if ($prog['total'] > 0): ?>
                <div style="margin:.6rem 0 1rem">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.35rem">
                        <strong style="font-size:.9rem">📢 Publication des articles programmés</strong>
                        <span class="muted" style="font-size:.85rem"><?= (int) $prog['published'] ?>/<?= (int) $prog['total'] ?> publiés · <strong style="color:#ff2e88"><?= (int) $prog['percent'] ?>%</strong></span>
                    </div>
                    <div style="height:14px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden;border:1px solid var(--glass-brd)">
                        <div style="height:100%;width:<?= (int) $prog['percent'] ?>%;border-radius:99px;background:linear-gradient(90deg,#ff2e88,#7a5cff);transition:width .4s ease"></div>
                    </div>
                    <p class="muted" style="font-size:.8rem;margin:.4rem 0 0">
                        Il reste <strong><?= (int) $prog['pending'] ?></strong> article(s) à publier (1 toutes les <?= (int) $autoInterval ?> h).
                        <?php if ($prog['pending'] > 0): ?>Tout sera en ligne dans ~<strong><?= e(($d = $prog['pending'] * $autoInterval) >= 24 ? round($d / 24, 1) . ' j' : $d . ' h') ?></strong>.<?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
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

    <!-- ILLUSTRATIONS IA (Higgsfield) -->
    <div class="glass" style="padding:1.4rem;border-radius:16px;margin:1rem 0;border:1px solid rgba(122,92,255,.28)">
        <h2 style="margin-top:0">🎨 Illustrations IA sur-mesure (Higgsfield)</h2>
        <p class="muted">Quand c'est activé, <strong>chaque article généré reçoit sa propre illustration</strong> créée par Higgsfield à partir de son prompt image — directement à la création. Sans clé, les articles utilisent la banque d'images (repli automatique, aucun blocage).</p>
        <p class="muted" style="font-size:.85rem">🪶 <strong>Toutes les images sont automatiquement compressées en 720p WebP</strong> (~80–140 Ko) avant d'être enregistrées → le site reste ultra-rapide, aucun risque de ralentissement.</p>

        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin:.4rem 0 1rem">
            <span style="font-size:1.3rem"><?= $imgEnabled ? '🟢' : ($imgOn ? '🟡' : '⚪') ?></span>
            <span><strong><?= $imgEnabled ? 'Illustrations IA actives' : ($imgOn && !$hasImgKey ? 'Activées mais clé manquante' : 'Illustrations IA désactivées (banque d\'images)') ?></strong></span>
        </div>

        <form method="post" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_image">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="ai_image_enabled" value="1" style="width:auto" <?= $imgOn ? 'checked' : '' ?>> <strong>Activer</strong>
            </label>
            <label style="flex:1;min-width:280px">Clé API Higgsfield <span class="muted">(format <code>KEY_ID:KEY_SECRET</code>)</span>
                <input type="password" name="higgsfield_key" placeholder="<?= $hasImgKey ? '•••••••• (déjà enregistrée)' : 'xxxxxxxx:yyyyyyyy' ?>" autocomplete="off" style="display:block;width:100%;margin-top:.3rem">
                <small class="muted">Laisse vide pour conserver la clé actuelle. Récupère la clé sur <a href="https://platform.higgsfield.ai" target="_blank" rel="noopener">platform.higgsfield.ai</a> (Settings → API keys). Ou variable d'env <code>HIGGSFIELD_KEY</code>.</small>
            </label>
            <button class="btn btn--primary" type="submit">Enregistrer</button>
        </form>
        <details style="margin-top:.8rem">
            <summary style="cursor:pointer;font-size:.85rem" class="muted">⚙️ Endpoint avancé (facultatif)</summary>
            <form method="post" style="margin-top:.6rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_image">
                <input type="hidden" name="ai_image_enabled" value="<?= $imgOn ? '1' : '0' ?>">
                <label style="flex:1;min-width:320px">URL de l'endpoint text-to-image
                    <input type="text" name="ai_image_endpoint" value="<?= e($imgEndpoint) ?>" placeholder="https://platform.higgsfield.ai/v1/flux-pro/kontext/max/text-to-image" style="display:block;width:100%;margin-top:.3rem;font-family:monospace;font-size:.8rem">
                    <small class="muted">Laisse vide pour l'endpoint par défaut. À changer seulement si l'API Higgsfield évolue.</small>
                </label>
                <label style="min-width:150px">Résolution de génération
                    <input type="text" name="ai_image_resolution" value="<?= e($imgRes) ?>" placeholder="720p" style="display:block;width:100%;margin-top:.3rem;font-family:monospace;font-size:.8rem">
                    <small class="muted">Facultatif (ex. <code>720p</code>, <code>1K</code>). Moins cher en crédits. Vide = standard. L'image est de toute façon ramenée en 720p WebP.</small>
                </label>
                <button class="btn btn--ghost" type="submit">Enregistrer</button>
            </form>
        </details>
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
    <h2 style="margin-top:2rem">🖼️ Articles IA &amp; illustrations (Higgsfield)</h2>
    <p class="muted">Si les illustrations IA sont activées, l'image est générée automatiquement à la création. Sinon, le prompt (jamais affiché aux visiteurs) reste dispo : clique <strong>🎨 Générer l'image</strong> pour créer l'illustration via Higgsfield à la demande, ou copie le prompt manuellement.</p>

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
                            <form method="post" style="display:inline">
                                <?= csrf_field() ?><input type="hidden" name="action" value="gen_image"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="btn btn--ghost" type="submit" <?= $imgEnabled ? '' : 'disabled title="Active les illustrations IA (clé Higgsfield) ci-dessus"' ?>>🎨 Générer l’image</button>
                            </form>
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
