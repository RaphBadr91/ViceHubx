<?php
/**
 * ViceHub X — Promeut le compte FONDATEUR en Administrateur (1 clic).
 *
 * Passe le compte du propriétaire (RaphBa91 / psiwaneraph@gmail.com) au rôle
 * 'admin'. Ne peut promouvoir QUE ce compte précis (aucun autre) : sans risque.
 *
 * À ouvrir UNE fois : https://vicehubx.com/make-admin.php → puis SUPPRIMER.
 */
require_once __DIR__ . '/config/config.php';

// Compte fondateur (verrouillé : le script ne touche à personne d'autre).
$OWNER_EMAIL = 'psiwaneraph@gmail.com';
$OWNER_USER  = 'RaphBa91';

$done = false; $msg = ''; $who = null;
try {
    $st = db()->prepare('SELECT id, username, email, role FROM users WHERE LOWER(email) = LOWER(?) OR username = ? LIMIT 1');
    $st->execute([$OWNER_EMAIL, $OWNER_USER]);
    $who = $st->fetch(PDO::FETCH_ASSOC);
    if (!$who) {
        $msg = "Compte introuvable. Crée d'abord ton compte sur le site avec l'e-mail {$OWNER_EMAIL} (ou l'identifiant {$OWNER_USER}), puis relance cette page.";
    } elseif (strtolower((string) $who['email']) !== strtolower($OWNER_EMAIL)) {
        // Sécurité : ne promouvoir QUE si l'e-mail fondateur correspond (bloque le
        // squat d'identifiant). Puis SUPPRIMER ce fichier.
        $msg = "Ce compte ne peut pas être promu automatiquement. Supprimez make-admin.php.";
    } elseif ($who['role'] === 'admin') {
        $done = true;
        $msg = "Le compte « {$who['username']} » est déjà Administrateur. ✅";
    } else {
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['admin', (int) $who['id']]);
        $done = true;
        $msg = "Le compte « {$who['username']} » ({$who['email']}) est désormais ADMINISTRATEUR FONDATEUR. 👑";
    }
} catch (Throwable $e) {
    $msg = 'Erreur : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow"><title>ViceHub X — Admin fondateur</title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></head>
<body><div class="admin-login-wrap"><div class="admin-card glass" style="width:min(560px,94vw)">
    <h1 style="text-align:center;font-size:1.2rem">Admin fondateur 👑</h1>
    <div class="alert alert--<?= $done ? 'ok' : 'err' ?>" style="margin:1rem 0"><?= e($msg) ?></div>
    <?php if ($done): ?>
        <p class="muted">Déconnecte-toi puis reconnecte-toi : tu auras accès au <strong>tableau de bord admin</strong> (menu en haut ou <code>/admin/dashboard.php</code>).</p>
        <a class="btn btn--primary" href="<?= e(url('admin/dashboard.php')) ?>" style="justify-content:center;width:100%">Aller à l'admin →</a>
    <?php endif; ?>
    <p class="alert alert--err" style="margin-top:1rem;font-size:.85rem">⚠️ Sécurité : <strong>supprime <code>make-admin.php</code></strong> maintenant.</p>
</div></div></body></html>
