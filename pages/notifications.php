<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$fr = lang() === 'fr';
$uid = (int) current_user()['id'];
$notifs = get_notifications($uid, 50);
mark_notifications_read($uid); // tout marquer lu à la visite
$SEO_TITLE = ($fr ? 'Notifications' : 'Notifications') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:720px">
    <span class="eyebrow">🔔 ViceHub X</span>
    <h1><?= $fr ? 'Tes notifications' : 'Your notifications' ?></h1>
    <?php if (!$notifs): ?>
        <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'Aucune notification pour l’instant. Participe au forum pour recevoir des réponses ! 💬' : 'No notifications yet.' ?></p>
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/forum.php'))) ?>"><?= $fr ? 'Aller au forum' : 'Go to forum' ?> →</a>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifs as $n): ?>
                <a class="notif<?= $n['is_read'] ? '' : ' notif--new' ?>" href="<?= e($n['link'] ? with_lang(url(ltrim($n['link'], '/'))) : '#') ?>">
                    <span class="notif__dot" aria-hidden="true"></span>
                    <span class="notif__body"><?= e($n['body']) ?></span>
                    <span class="notif__date muted"><?= e(substr((string) $n['created_at'], 0, 16)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
