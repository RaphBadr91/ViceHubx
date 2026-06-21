<?php
/**
 * ViceHub X — Page d'accueil immersive (Vice City OS).
 */
require_once __DIR__ . '/config/config.php';

/* --- Newsletter (traitement simple, sans API) --- */
$newsletter_msg = null;
if (($_POST['action'] ?? '') === 'newsletter') {
    if (verify_csrf()) {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if ($email) {
            $stmt = db()->prepare(
                'INSERT IGNORE INTO newsletter_subscribers (email, lang) VALUES (?, ?)'
            );
            $stmt->execute([$email, lang()]);
            $newsletter_msg = ['ok', t('newsletter_ok')];
        } else {
            $newsletter_msg = ['err', 'E-mail invalide.'];
        }
    }
}

$breaking = get_articles(['category' => 'news', 'lang' => lang(), 'limit' => 3]);
$guides   = get_articles(['category' => 'guides', 'lang' => lang(), 'limit' => 3]);
$leaks    = get_articles(['category' => 'leaks', 'lang' => lang(), 'limit' => 3]);
$trailers = get_trailer_analyses();
$zones    = array_slice(get_map_zones(), 0, 4);
$vehicles = array_slice(get_vehicles(), 0, 3);
$deals    = get_deals();

$modules = [
    ['🧠', 'News AI',     'news.php'],
    ['🗺️', 'Map Lab',     'map.php'],
    ['🎬', 'Trailer Lab', 'trailer-lab.php'],
    ['🔬', 'Leaks Lab',   'leaks-lab.php'],
    ['🚗', 'Vehicles',    'vehicles.php'],
    ['🎭', 'Characters',  'characters.php'],
    ['💬', 'Community',   'community.php'],
    ['🎮', 'Deals',       'deals.php'],
];

$BODY_CLASS = 'is-home';
$JSONLD = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    'name'     => 'ViceHub X',
    'description' => lang() === 'fr'
        ? 'Média indépendant GTA VI : news, guides, leaks et analyses.'
        : 'Independent GTA VI media: news, guides, leaks and analysis.',
];
require __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero">
    <canvas class="hero__canvas" id="vh-canvas" aria-hidden="true"></canvas>
    <div class="hero__veil" aria-hidden="true"></div>
    <span class="palm palm--l" aria-hidden="true">🌴</span>
    <span class="palm palm--r" aria-hidden="true">🌴</span>

    <div class="hero__content">
        <span class="hero__badge">✦ <?= e(t('hero_badge')) ?></span>
        <h1>Vice<span class="grad">Hub</span> X</h1>
        <p class="hero__sub"><?= e(lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN) ?></p>
        <div class="hero__actions">
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('hero_cta_news')) ?></a>
            <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('hero_cta_map')) ?></a>
        </div>
    </div>
    <div class="scroll-cue" aria-hidden="true"></div>
</section>

<!-- ============ VICE CITY OS ============ -->
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow"><?= e(t('os_subtitle')) ?></span>
            <h2><span class="accent"><?= e(t('os_title')) ?></span></h2>
        </div>
    </div>
    <div class="os-grid">
        <?php foreach ($modules as [$ico, $name, $href]): ?>
            <a class="os-module glass reveal" href="<?= e(with_lang(url('pages/' . $href))) ?>">
                <span class="ico" aria-hidden="true"><?= $ico ?></span>
                <span class="name"><?= e($name) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ BREAKING NEWS ============ -->
<section class="section">
    <div class="section-head">
        <h2>🔴 <?= e(t('breaking')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <?php if ($breaking): ?>
    <div class="cards">
        <?php foreach ($breaking as $a): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🌆</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e($a['category_name'] ?? 'News') ?></span>
                    <h3 class="card__title"><?= e($a['title']) ?></h3>
                    <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                    <div class="card__meta">
                        <?= badge_html($a['badge']) ?>
                        <span class="card__date"><?= e(fmt_date($a['published_at'])) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted"><?= e(t('no_content')) ?></p>
    <?php endif; ?>
</section>

<!-- ============ GUIDES ============ -->
<section class="section">
    <div class="section-head">
        <h2>📘 <?= e(t('popular_guides')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/guides.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="cards">
        <?php foreach ($guides as $a): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🧭</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e(t('nav_guides')) ?></span>
                    <h3 class="card__title"><?= e($a['title']) ?></h3>
                    <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                    <span class="card__date"><?= e(fmt_date($a['published_at'])) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ LEAKS LAB ============ -->
<section class="section">
    <div class="section-head">
        <h2>🔬 <?= e(t('leaks_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/leaks-lab.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="cards">
        <?php foreach ($leaks as $a): ?>
            <article class="card glass reveal">
                <div class="card__body">
                    <?= badge_html($a['badge']) ?>
                    <h3 class="card__title"><?= e($a['title']) ?></h3>
                    <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ TRAILER LAB ============ -->
<section class="section">
    <div class="section-head">
        <h2>🎬 <?= e(t('trailer_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/trailer-lab.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <?php foreach ($trailers as $row): ?>
        <div class="trailer-row glass reveal">
            <span class="timecode"><?= e($row['timecode']) ?></span>
            <div>
                <strong><?= e($row['description']) ?></strong><br>
                <span class="muted"><?= e(t('clue')) ?> : <?= e($row['clue']) ?></span>
            </div>
            <span class="impo" title="<?= e(importance_label((int) $row['importance'])) ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= (int) $row['importance'] ? 'on' : '' ?>"></i>
                <?php endfor; ?>
            </span>
        </div>
    <?php endforeach; ?>
</section>

<!-- ============ MAP PREVIEW ============ -->
<section class="section">
    <div class="section-head">
        <h2>🗺️ <?= e(t('map_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="map-wrap glass">
        <div class="map-grid" aria-hidden="true"></div>
        <div class="map-zones">
            <?php foreach ($zones as $z): ?>
                <button class="zone" aria-expanded="false">
                    <h3><?= e($z['name']) ?></h3>
                    <p><?= e($z['description']) ?></p>
                    <div class="zone-info"><?= e($z['info']) ?></div>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ VEHICLES ============ -->
<section class="section">
    <div class="section-head">
        <h2>🚗 <?= e(t('vehicles_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/vehicles.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="cards">
        <?php foreach ($vehicles as $v): ?>
            <article class="card glass reveal">
                <div class="card__media"><span aria-hidden="true">🏎️</span></div>
                <div class="card__body">
                    <span class="card__cat"><?= e($v['type']) ?></span>
                    <h3 class="card__title"><?= e($v['name']) ?></h3>
                    <p class="card__excerpt"><?= e(t('veh_speed')) ?> : <?= e($v['speed']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="section">
    <div class="banner glass reveal">
        <h2>📨 <?= e(t('newsletter')) ?></h2>
        <p class="muted"><?= e(t('newsletter_text')) ?></p>
        <?php if ($newsletter_msg): ?>
            <div class="alert alert--<?= e($newsletter_msg[0]) ?>"><?= e($newsletter_msg[1]) ?></div>
        <?php endif; ?>
        <form method="post" class="inline-form" style="justify-content:center;max-width:480px;margin:1rem auto 0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="newsletter">
            <input type="email" name="email" required placeholder="<?= e(t('newsletter_ph')) ?>" aria-label="email">
            <button class="btn btn--primary" type="submit"><?= e(t('newsletter_btn')) ?></button>
        </form>
    </div>
</section>

<!-- ============ DEALS ============ -->
<section class="section">
    <div class="section-head">
        <h2>🎮 <?= e(t('deals_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="cards">
        <?php foreach ($deals as $d): ?>
            <a class="deal glass reveal" href="<?= e($d['url']) ?>" rel="sponsored nofollow" target="_blank">
                <span class="tag"><?= e($d['platform']) ?> · <?= e($d['badge']) ?></span>
                <h3><?= e($d['title']) ?></h3>
                <p class="muted"><?= e($d['description']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
