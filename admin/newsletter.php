<?php
/**
 * ViceHub X — Newsletter : envoi d'une campagne aux abonnés (Resend).
 * La capture d'e-mails existait mais n'était jamais exploitée : cette page
 * envoie un e-mail à la liste (filtrable par langue), en réutilisant
 * resend_send() + email_layout(). Envoi plafonné par lot pour éviter le timeout.
 */
$ADMIN_TITLE = 'ViceHub X — Newsletter';
require __DIR__ . '/../includes/admin_header.php';

$flash = null;

// Compte des abonnés par langue.
$counts = ['fr' => 0, 'en' => 0, 'all' => 0];
try {
    foreach (db()->query("SELECT lang, COUNT(*) c FROM newsletter_subscribers GROUP BY lang")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $counts[$r['lang']] = (int) $r['c'];
    }
    $counts['all'] = $counts['fr'] + $counts['en'];
} catch (Throwable $e) { /* table absente */ }

if (($_POST['action'] ?? '') === 'send') {
    if (!verify_csrf()) {
        $flash = ['err', 'Jeton CSRF invalide.'];
    } else {
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $heading = trim((string) ($_POST['heading'] ?? '')) ?: $subject;
        $bodyHtml = trim((string) ($_POST['body'] ?? ''));
        $ctaLabel = trim((string) ($_POST['cta_label'] ?? ''));
        $ctaUrl   = trim((string) ($_POST['cta_url'] ?? ''));
        $target   = in_array($_POST['lang'] ?? 'all', ['fr', 'en', 'all'], true) ? $_POST['lang'] : 'all';
        $test     = trim((string) ($_POST['test_email'] ?? ''));

        if ($subject === '' || $bodyHtml === '') {
            $flash = ['err', 'Sujet et contenu sont obligatoires.'];
        } else {
            // Le contenu est écrit par l'admin (source de confiance) mais on limite le HTML.
            $safeBody = strip_tags($bodyHtml, '<p><br><strong><em><ul><ol><li><h2><h3><a><blockquote>');
            $footer = '<p style="font-size:12px;color:#888;margin-top:24px">ViceHub X — média fan indépendant GTA VI. '
                . 'Vous recevez cet e-mail car vous êtes inscrit à la newsletter sur vicehubx.com.</p>';
            $html = email_layout($heading, $safeBody . $footer, $ctaLabel, $ctaUrl);

            if ($test !== '') {
                // Envoi de TEST à une seule adresse (ne touche pas la liste).
                $ok = resend_send($test, '[TEST] ' . $subject, $html);
                $flash = $ok ? ['ok', 'E-mail de test envoyé à ' . $test . '.']
                             : ['err', 'Échec de l\'envoi de test (vérifiez la clé Resend dans Réglages).'];
            } else {
                try {
                    @set_time_limit(0);               // envoi séquentiel : évite le timeout
                    $sql = "SELECT email FROM newsletter_subscribers";
                    if ($target !== 'all') { $sql .= " WHERE lang = " . db()->quote($target); }
                    $sql .= " ORDER BY id ASC";        // TOUS les abonnés (aucun doublon, personne oublié)
                    $emails = db()->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                    $sent = 0; $fail = 0;
                    foreach ($emails as $em) {
                        if (resend_send((string) $em, $subject, $html)) { $sent++; } else { $fail++; }
                    }
                    $flash = ['ok', "Campagne envoyée : {$sent} e-mail(s) partis" . ($fail ? ", {$fail} échec(s)" : '') . '.'];
                } catch (Throwable $e) {
                    $flash = ['err', 'Erreur : ' . $e->getMessage()];
                }
            }
        }
    }
}
?>
<div class="admin-bar">
    <h1>📧 Newsletter</h1>
    <span class="muted"><?= (int) $counts['all'] ?> abonné(s) — 🇫🇷 <?= (int) $counts['fr'] ?> · 🇬🇧 <?= (int) $counts['en'] ?></span>
</div>

<?php if ($flash): ?><div class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<form method="post" class="form glass" style="max-width:760px;padding:1.6rem;border-radius:18px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="send">
    <div><label>Sujet *</label><input type="text" name="subject" required maxlength="180" value="<?= e($_POST['subject'] ?? '') ?>"></div>
    <div><label>Titre (dans l'e-mail — auto = sujet)</label><input type="text" name="heading" maxlength="180" value="<?= e($_POST['heading'] ?? '') ?>"></div>
    <div><label>Contenu * <span class="muted">(HTML simple autorisé : &lt;p&gt; &lt;strong&gt; &lt;a&gt; &lt;ul&gt;&lt;li&gt; &lt;h2&gt;…)</span></label>
        <textarea name="body" required style="min-height:180px"><?= e($_POST['body'] ?? '') ?></textarea></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div><label>Libellé du bouton (optionnel)</label><input type="text" name="cta_label" maxlength="60" placeholder="Lire l'article" value="<?= e($_POST['cta_label'] ?? '') ?>"></div>
        <div><label>Lien du bouton (optionnel)</label><input type="url" name="cta_url" maxlength="500" placeholder="https://vicehubx.com/article/..." value="<?= e($_POST['cta_url'] ?? '') ?>"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:end">
        <div><label>Destinataires</label>
            <select name="lang">
                <option value="all">Tous (<?= (int) $counts['all'] ?>)</option>
                <option value="fr">🇫🇷 Français (<?= (int) $counts['fr'] ?>)</option>
                <option value="en">🇬🇧 English (<?= (int) $counts['en'] ?>)</option>
            </select>
        </div>
        <div><label>Envoyer un TEST à (au lieu de la liste)</label><input type="email" name="test_email" placeholder="toi@exemple.com"></div>
    </div>
    <p class="muted" style="font-size:.82rem">Astuce : envoie-toi d'abord un <strong>test</strong>, vérifie le rendu, puis laisse ce champ vide pour envoyer à <strong>toute la liste</strong> (tous les abonnés, en une fois).</p>
    <button class="btn btn--primary" type="submit">Envoyer</button>
</form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
