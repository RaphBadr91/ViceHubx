<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$fr = lang() === 'fr';
$me = (int) current_user()['id'];

$other = null;
$uname = trim((string) ($_GET['u'] ?? ''));
if ($uname !== '') {
    $other = get_user_by_username($uname);
}

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf() && $other) {
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body !== '') {
            send_message($me, (int) $other['id'], $body);
        }
    }
    redirect(with_lang(url('pages/messages.php?u=' . urlencode($uname))));
}

$SEO_TITLE = ($fr ? 'Messagerie' : 'Messages') . ' — ' . APP_NAME;
$ROBOTS = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:760px">
    <span class="eyebrow">💌 ViceHub X</span>

    <?php if ($other && (int) $other['id'] !== $me): ?>
        <?php $msgs = get_conversation($me, (int) $other['id']); $name = $other['display_name'] ?: $other['username']; ?>
        <div class="section-head">
            <h1 style="font-size:1.4rem"><?= $fr ? 'Discussion avec' : 'Chat with' ?> <a href="<?= e(with_lang(url('pages/profil.php?u=' . urlencode($other['username'])))) ?>" style="color:inherit"><?= e($name) ?></a></h1>
            <a class="link-all" href="<?= e(with_lang(url('pages/messages.php'))) ?>">← <?= $fr ? 'Boîte de réception' : 'Inbox' ?></a>
        </div>
        <div class="dm-thread">
            <?php if (!$msgs): ?><p class="muted"><?= $fr ? 'Démarre la conversation !' : 'Start the conversation!' ?></p><?php endif; ?>
            <?php foreach ($msgs as $m): $mine = (int) $m['from_id'] === $me; ?>
                <div class="dm-bubble<?= $mine ? ' dm-bubble--me' : '' ?>">
                    <?= nl2br(e($m['body'])) ?>
                    <span class="dm-time"><?= e(substr((string) $m['created_at'], 11, 5)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" class="dm-form">
            <?= csrf_field() ?>
            <input type="text" name="body" required autocomplete="off" placeholder="<?= $fr ? 'Écris ton message…' : 'Write your message…' ?>" maxlength="4000">
            <button class="btn btn--primary" type="submit"><?= $fr ? 'Envoyer' : 'Send' ?></button>
        </form>
    <?php else: ?>
        <h1><?= $fr ? 'Messagerie' : 'Messages' ?></h1>
        <?php $convs = get_conversations($me); ?>
        <?php if (!$convs): ?>
            <p class="muted" style="margin-top:1.4rem"><?= $fr ? 'Aucune conversation. Va sur un profil et clique « Envoyer un message » pour démarrer. 💬' : 'No conversations yet.' ?></p>
        <?php else: ?>
            <div class="dm-list">
                <?php foreach ($convs as $c): ?>
                    <a class="dm-conv<?= $c['unread'] ? ' dm-conv--unread' : '' ?>" href="<?= e(with_lang(url('pages/messages.php?u=' . urlencode($c['username'])))) ?>">
                        <span class="dm-conv__name"><?= e($c['name']) ?><?php if ($c['unread']): ?> <span class="cart-badge" style="position:static"><?= (int) $c['unread'] ?></span><?php endif; ?></span>
                        <span class="muted dm-conv__last"><?= e(mb_strimwidth(strip_tags((string) ($c['last']['body'] ?? '')), 0, 60, '…')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
