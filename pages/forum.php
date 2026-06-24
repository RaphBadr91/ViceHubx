<?php
require_once dirname(__DIR__) . '/config/config.php';
$cats = get_forum_categories();
$SEO_TITLE = (lang() === 'fr' ? 'Forum communautaire' : 'Community forum') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr'
    ? 'Forum GTA VI de ViceHub X : discussions, théories, leaks, guides et entraide entre fans de Vice City.'
    : 'ViceHub X GTA VI forum: discussions, theories, leaks, guides and community help.';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">💬 ViceHub X</span>
            <h1><?= lang() === 'fr' ? 'Forum communautaire' : 'Community forum' ?></h1>
        </div>
        <?php if (is_logged_in()): ?>
            <a class="link-all" href="<?= e(with_lang(url('pages/account.php'))) ?>"><?= lang() === 'fr' ? 'Mon compte' : 'My account' ?> →</a>
        <?php else: ?>
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/register.php'))) ?>"><?= lang() === 'fr' ? 'Rejoindre' : 'Join' ?> →</a>
        <?php endif; ?>
    </div>

    <?php $tops = leaderboard(5); if ($tops): ?>
    <div class="topmembers">
        <span class="muted" style="font-size:.82rem"><?= lang() === 'fr' ? '🏆 Top membres :' : '🏆 Top members:' ?></span>
        <?php foreach ($tops as $m): ?>
            <a class="topmember" href="<?= e(with_lang(url('pages/profil.php?u=' . urlencode($m['username'])))) ?>"><?= $m['rank']['emoji'] ?> <?= e($m['display_name'] ?: $m['username']) ?> <span class="muted"><?= (int) $m['xp'] ?> XP</span></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="forum-cats">
        <?php foreach ($cats as $c): ?>
            <a class="forum-cat glass" href="<?= e(with_lang(url('pages/forum-category.php?cat=' . urlencode($c['slug'])))) ?>">
                <span class="forum-cat__ico"><?= e($c['icon'] ?: '💬') ?></span>
                <div class="forum-cat__body">
                    <h3><?= e($c['name']) ?></h3>
                    <p class="muted"><?= e($c['description']) ?></p>
                </div>
                <div class="forum-cat__stats">
                    <span><strong><?= (int) $c['thread_count'] ?></strong> <?= lang() === 'fr' ? 'sujets' : 'topics' ?></span>
                    <span><strong><?= (int) $c['post_count'] ?></strong> <?= lang() === 'fr' ? 'messages' : 'posts' ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="muted" style="margin-top:1.6rem;font-size:.85rem">
        <?= lang() === 'fr'
            ? 'Soyez respectueux : pas de spam, d’insultes ni de contenu illégal. Les leaks restent des rumeurs non confirmées.'
            : 'Be respectful: no spam, insults or illegal content. Leaks remain unconfirmed rumors.' ?>
    </p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
