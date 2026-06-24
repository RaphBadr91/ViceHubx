<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$top = leaderboard(30);
$SEO_TITLE = ($fr ? 'Classement des membres — Top contributeurs' : 'Members leaderboard') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Le classement des membres les plus actifs de la communauté GTA VI de ViceHub X. Gagne de l’XP, grimpe les rangs et deviens une Légende de Leonida.'
    : 'The leaderboard of ViceHub X’s most active GTA VI community members.';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🏆 ViceHub X</span>
    <h1><?= $fr ? 'Classement des membres' : 'Members leaderboard' ?></h1>
    <p class="muted" style="max-width:720px"><?= $fr
        ? 'Plus tu participes au forum, plus tu gagnes d’XP et grimpes les rangs. Objectif : devenir une Légende de Leonida. ⭐'
        : 'The more you post, the more XP you earn and the higher you climb. Goal: become a Legend of Leonida.' ?></p>

    <div class="ranks-legend">
        <?php foreach (rank_tiers() as $t): ?>
            <span class="rank-chip"><?= $t[2] ?> <?= e($t[1]) ?> <small class="muted"><?= (int) $t[0] ?> XP</small></span>
        <?php endforeach; ?>
    </div>

    <?php if (!$top): ?>
        <p class="muted"><?= e(t('no_content')) ?></p>
    <?php else: ?>
    <ol class="leaderboard">
        <?php foreach ($top as $i => $m): $podium = $i < 3; ?>
            <li class="lb-row glass<?= $podium ? ' lb-row--top' : '' ?>">
                <span class="lb-pos"><?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '#' . ($i + 1))) ?></span>
                <span class="lb-name"><a href="<?= e(with_lang(url('pages/profil.php?u=' . urlencode($m['username'])))) ?>" style="color:inherit"><?= e($m['display_name'] ?: $m['username']) ?></a>
                    <span class="rank-chip"><?= $m['rank']['emoji'] ?> <?= e($m['rank']['name']) ?></span>
                </span>
                <span class="lb-stats muted"><?= (int) $m['posts'] ?> <?= $fr ? 'msg' : 'posts' ?> · <?= (int) $m['threads'] ?> <?= $fr ? 'sujets' : 'topics' ?></span>
                <span class="lb-xp"><?= (int) $m['xp'] ?> XP</span>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>

    <div class="banner glass" style="text-align:center;margin-top:2rem">
        <h2><?= $fr ? 'Pas encore classé ?' : 'Not ranked yet?' ?></h2>
        <p class="muted"><?= $fr ? 'Lance-toi sur le forum et fais grimper ton rang.' : 'Jump into the forum and climb the ranks.' ?></p>
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/forum.php'))) ?>"><?= $fr ? 'Aller au forum' : 'Go to the forum' ?> →</a>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
