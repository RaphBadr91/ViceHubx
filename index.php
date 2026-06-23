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
$featured_products = get_featured_products(4);

$hero_video = trim((string) get_setting('hero_video', ''));
// Vidéo locale générée : utilisée par défaut si présente (réglage admin prioritaire)
if ($hero_video === '' && is_file(ROOT_PATH . '/public/assets/video/hero.mp4')) {
    $hero_video = asset('video/hero.mp4');
}
$hero_poster = is_file(ROOT_PATH . '/public/assets/img/hero-poster.png') ? asset('img/hero-poster.png') : '';
$deadline   = release_date();

$modules = [
    ['news',       'News AI',     'Actus & analyses',        'News & analysis',     'news.php'],
    ['map',        'Map Lab',     'La carte de Leonida',     'The Leonida map',     'map.php'],
    ['trailer',    'Trailer Lab', 'Décryptage des trailers', 'Trailer breakdowns',  'trailer-lab.php'],
    ['leaks',      'Leaks Lab',   'Fuites & fiabilité',      'Leaks & reliability', 'leaks-lab.php'],
    ['vehicles',   'Vehicles',    'Véhicules & conduite',    'Vehicles & driving',  'vehicles.php'],
    ['characters', 'Characters',  'Jason, Lucia & cie',      'Jason, Lucia & co',   'characters.php'],
    ['community',  'Community',   'Sondages & débats',       'Polls & debates',     'community.php'],
    ['deals',      'Deals',       'Bons plans gaming',       'Gaming deals',        'deals.php'],
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
    <?php if ($hero_video !== ''): ?>
        <video class="hero__video" autoplay muted loop playsinline preload="auto"
               <?= $hero_poster !== '' ? 'poster="' . e($hero_poster) . '"' : '' ?> aria-hidden="true">
            <source src="<?= e($hero_video) ?>">
        </video>
    <?php else: ?>
        <canvas class="hero__canvas" id="vh-canvas" aria-hidden="true"></canvas>
    <?php endif; ?>
    <div class="hero__veil" aria-hidden="true"></div>
    <span class="palm palm--l" aria-hidden="true">🌴</span>
    <span class="palm palm--r" aria-hidden="true">🌴</span>

    <div class="hero__content">
        <span class="hero__kicker">✦ <?= e(t('hero_badge')) ?></span>
        <h1 class="hero__wm"><span>ViceHub</span><b class="grad">X</b></h1>
        <p class="hero__release"><b>GTA VI</b> — <?= e(release_human()) ?></p>
        <p class="hero__sub"><?= e(lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN) ?></p>

        <!-- Compte à rebours jusqu'à la sortie -->
        <div class="countdown" data-deadline="<?= e($deadline) ?>" role="timer" aria-label="<?= e(t('cd_title')) ?>">
            <span class="cd-title"><?= e(t('cd_title')) ?></span>
            <div class="cd-tiles">
                <div class="cd-tile"><b data-cd="d">--</b><i><?= e(t('cd_days')) ?></i></div>
                <div class="cd-sep">:</div>
                <div class="cd-tile"><b data-cd="h">--</b><i><?= e(t('cd_hours')) ?></i></div>
                <div class="cd-sep">:</div>
                <div class="cd-tile"><b data-cd="m">--</b><i><?= e(t('cd_min')) ?></i></div>
                <div class="cd-sep">:</div>
                <div class="cd-tile"><b data-cd="s">--</b><i><?= e(t('cd_sec')) ?></i></div>
            </div>
            <span class="cd-done"><?= e(t('cd_released')) ?></span>
        </div>

        <div class="hero__actions">
            <a class="btn btn--primary" href="#trailer">▶ <?= e(t('cta_trailer')) ?></a>
            <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('hero_cta_news')) ?></a>
        </div>
    </div>
    <div class="scroll-cue" aria-hidden="true"></div>
</section>

<!-- ============ BANDE-ANNONCE ============ -->
<section class="section" id="trailer">
    <div class="section-head">
        <div>
            <span class="eyebrow"><?= lang() === 'fr' ? 'À ne pas manquer' : "Don't miss it" ?></span>
            <h2><?= e(t('trailer_section')) ?></h2>
        </div>
        <a class="link-all" href="<?= e(with_lang(url('pages/trailer-lab.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <?php
    $trailer_url = trim((string) get_setting('trailer_url', ''));
    // ID YouTube depuis le réglage admin, sinon trailer officiel par défaut
    $yt_id = 'VQRLujxTm3c';
    if ($trailer_url !== '' && preg_match('~(?:youtu\.be/|v=|embed/)([A-Za-z0-9_-]{11})~', $trailer_url, $m)) {
        $yt_id = $m[1];
    }
    ?>
    <a class="trailer-hero has-thumb reveal" href="https://www.youtube.com/watch?v=<?= e($yt_id) ?>"
       data-trailer="<?= e($yt_id) ?>" target="_blank" rel="noopener">
        <div class="trailer-hero__bg" style="background-image:url('<?= e(yt_thumb($yt_id)) ?>')" aria-hidden="true"></div>
        <div class="trailer-hero__grid" aria-hidden="true"></div>
        <span class="play-btn" aria-hidden="true">▶</span>
        <div class="trailer-hero__cap">
            <span class="eyebrow"><?= lang() === 'fr' ? 'Bande-annonce officielle' : 'Official trailer' ?></span>
            <h2><?= lang() === 'fr' ? 'Bienvenue à Vice City' : 'Welcome to Vice City' ?></h2>
        </div>
    </a>
</section>

<!-- Lightbox bande-annonce -->
<div class="lightbox" id="trailer-lightbox" hidden>
    <button class="lightbox__close" type="button" aria-label="<?= lang() === 'fr' ? 'Fermer' : 'Close' ?>">&times;</button>
    <div class="lightbox__frame"><div class="video-embed" id="lightbox-embed"></div></div>
</div>

<!-- ============ BREAKING NEWS (juste sous le trailer) ============ -->
<section class="section">
    <div class="section-head">
        <h2>🔴 <?= e(t('breaking')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <?php if ($breaking): ?>
    <div class="cards">
        <?php foreach ($breaking as $a): ?>
            <article class="card glass reveal">
                <?= media_html($a['image'] ?? '', '🌆') ?>
                <div class="card__body">
                    <span class="card__cat"><?= e($a['category_name'] ?? 'News') ?></span>
                    <h3 class="card__title"><a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>"><?= e($a['title']) ?></a></h3>
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

<?= ad_slot(get_setting('adsense_slot', '')) ?>

<!-- ============ VICE CITY OS ============ -->
<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow"><?= e(t('os_subtitle')) ?></span>
            <h2><span class="accent"><?= e(t('os_title')) ?></span></h2>
        </div>
    </div>
    <div class="os-grid">
        <?php foreach ($modules as $i => [$key, $name, $descFr, $descEn, $href]): ?>
            <a class="os-card os--<?= e($key) ?> glass reveal" style="--i:<?= $i ?>" href="<?= e(with_lang(url('pages/' . $href))) ?>">
                <span class="os-card__icon"><?= os_icon($key) ?></span>
                <span class="os-card__txt">
                    <span class="os-card__name"><?= e($name) ?></span>
                    <span class="os-card__desc"><?= e(lang() === 'fr' ? $descFr : $descEn) ?></span>
                </span>
                <span class="os-card__arrow" aria-hidden="true">→</span>
            </a>
        <?php endforeach; ?>
    </div>
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
                <?= media_html($a['image'] ?? '', '🧭') ?>
                <div class="card__body">
                    <span class="card__cat"><?= e(t('nav_guides')) ?></span>
                    <h3 class="card__title"><a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>"><?= e($a['title']) ?></a></h3>
                    <p class="card__excerpt"><?= e($a['excerpt']) ?></p>
                    <span class="card__date"><?= e(fmt_date($a['published_at'])) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?= ad_slot('', lang() === 'fr' ? 'Publicité' : 'Advertisement') ?>

<!-- ============ LEAKS LAB ============ -->
<section class="section">
    <div class="section-head">
        <h2>🔬 <?= e(t('leaks_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/leaks-lab.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <div class="leak-grid">
        <?php foreach ($leaks as $a): $rel = badge_reliability($a['badge']); ?>
            <a class="leak-card glass reveal" href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>">
                <div class="leak-card__top">
                    <?= badge_html($a['badge']) ?>
                    <span class="leak-rel"><?= $rel ?><small>%</small></span>
                </div>
                <h3 class="leak-card__title"><?= e($a['title']) ?></h3>
                <p class="leak-card__txt"><?= e($a['excerpt']) ?></p>
                <div class="leak-gauge" style="--rel:<?= $rel ?>%"><span></span></div>
                <span class="leak-foot"><?= lang() === 'fr' ? 'Indice de fiabilité' : 'Reliability score' ?></span>
            </a>
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
    <?php $map_url = trim((string) get_setting('map_url', '')) ?: 'https://map.stateofleonida.net/?map=vi&lat=3904.00&lng=-10452.00'; ?>
    <?php if ($map_url !== ''): ?>
        <div class="map-embed glass" style="height:min(62vh,560px)">
            <iframe src="<?= e($map_url) ?>" title="Carte interactive GTA VI — Leonida"
                    loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
            <a class="map-embed__open btn btn--ghost" href="<?= e($map_url) ?>" target="_blank" rel="noopener"><?= lang() === 'fr' ? 'Plein écran' : 'Fullscreen' ?> ↗</a>
        </div>
        <p class="muted" style="font-size:.8rem;margin:.7rem 0 0">
            <?= lang() === 'fr'
                ? 'Carte interactive communautaire. Si elle ne s’affiche pas, cliquez sur « Plein écran ».'
                : 'Community interactive map. If it does not load, click “Fullscreen”.' ?>
        </p>
        <div class="map-zones map-zones--row">
            <?php foreach ($zones as $z): ?>
                <button class="zone" aria-expanded="false">
                    <h3>📍 <?= e($z['name']) ?></h3>
                    <p><?= e($z['description']) ?></p>
                    <div class="zone-info"><?= e($z['info']) ?></div>
                </button>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php $map_img = is_file(ROOT_PATH . '/public/assets/img/scenes/map.png') ? asset('img/scenes/map.png') : ''; ?>
        <div class="map-wrap glass<?= $map_img ? ' has-map' : '' ?>"<?= $map_img ? ' style="--map:url(\'' . e($map_img) . '\')"' : '' ?>>
            <div class="map-canvas" aria-hidden="true"></div>
            <div class="map-grid" aria-hidden="true"></div>
            <div class="map-scan" aria-hidden="true"></div>
            <div class="map-zones">
                <?php foreach ($zones as $z): ?>
                    <button class="zone" aria-expanded="false">
                        <h3>📍 <?= e($z['name']) ?></h3>
                        <p><?= e($z['description']) ?></p>
                        <div class="zone-info"><?= e($z['info']) ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
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
                <?= media_html($v['image'] ?? '', '🏎️') ?>
                <div class="card__body">
                    <span class="card__cat"><?= e($v['type']) ?></span>
                    <h3 class="card__title"><?= e($v['name']) ?></h3>
                    <p class="card__excerpt"><?= e(t('veh_speed')) ?> : <?= e($v['speed']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============ UNIVERS DE VICE CITY ============ -->
<?php
$scene_dir = ROOT_PATH . '/public/assets/img/scenes';
$scene_defs = [
    ['night',        'Vice City la nuit',   'Vice City by night',  'Night drive'],
    ['beach-cruise', 'Boulevard côtier',    'Coastal boulevard',   'Golden hour'],
    ['aerial',       'Leonida vue du ciel', 'Leonida from above',  'Aérien'],
    ['police',       'Avis de recherche',   'Wanted',              'Police'],
    ['heli',         'Soutien aérien',      'Air support',         'Hélico'],
    ['plane',        'Survol côtier',       'Coastal flyover',     'Avion'],
    ['marina',       'Marina & yachts',     'Marina & yachts',     'Bateau'],
    ['beachlife',    'Vie de plage',        'Beach life',          'Plage'],
];
$scene_img = static function (string $dir, string $key): ?string {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $g = glob($dir . '/*' . $key . '*.' . $ext);
        if ($g) return $g[0];
    }
    return null;
};
$days_left = max(0, (int) floor((strtotime(release_date()) - time()) / 86400));
?>
<section class="section universe" id="univers">
    <div class="section-head">
        <div>
            <span class="eyebrow"><?= lang() === 'fr' ? "Plonge dans l'ambiance" : 'Step into the vibe' ?></span>
            <h2><?= lang() === 'fr' ? 'L’univers de Vice City' : 'The world of Vice City' ?></h2>
        </div>
        <a class="link-all" href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('hero_cta_map')) ?> →</a>
    </div>

    <div class="scene-grid">
        <?php foreach ($scene_defs as $i => [$key, $fr, $en, $tag]):
            $img = ($scene_img)($scene_dir, $key); ?>
            <div class="scene scene--<?= e($key) ?><?= $i === 0 ? ' scene--feat' : '' ?>"<?= $img ? ' style="--scene-op:0"' : '' ?>>
                <?php if ($img): ?>
                    <span class="scene-bg" style="background-image:url('<?= e(asset('img/scenes/' . basename($img))) ?>')"></span>
                <?php endif; ?>
                <div class="scene-cap">
                    <span class="scene-tag"><?= e($tag) ?></span>
                    <span class="scene-title"><?= e(lang() === 'fr' ? $fr : $en) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bandeau référence (E-E-A-T) -->
    <div class="refband" style="margin-top:1.6rem">
        <div class="ref glass"><div class="big"><?= $days_left ?></div><small><?= lang() === 'fr' ? 'jours avant la sortie' : 'days until launch' ?></small></div>
        <div class="ref glass"><div class="big">100%</div><small><?= lang() === 'fr' ? 'Indépendant & non officiel' : 'Independent & unofficial' ?></small></div>
        <div class="ref glass"><div class="big">FR · EN</div><small><?= lang() === 'fr' ? 'Site bilingue' : 'Bilingual site' ?></small></div>
        <div class="ref glass"><div class="big">SEO</div><small><?= lang() === 'fr' ? 'Optimisé pour Google' : 'Search-optimised' ?></small></div>
    </div>
</section>

<!-- ============ BOUTIQUE (teaser) ============ -->
<?php if ($featured_products): ?>
<section class="section">
    <div class="section-head">
        <h2>🛍️ <?= e(t('shop_section')) ?></h2>
        <a class="link-all" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= e(t('view_all')) ?> →</a>
    </div>
    <?php $cat_emoji = ['poster' => '🖼️', 'game' => '🎮', 'console' => '🕹️', 'apparel' => '👕', 'accessory' => '🎧', 'collectible' => '🏆']; ?>
    <div class="shop-teaser-grid">
        <?php foreach ($featured_products as $p): ?>
            <article class="product glass reveal">
                <a class="product__media" href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>">
                    <?php if (!empty($p['badge'])): ?><span class="product__badge"><?= e($p['badge']) ?></span><?php endif; ?>
                    <span class="card__emoji" aria-hidden="true"><?= $cat_emoji[$p['category']] ?? '🛍️' ?></span>
                    <?php if (!empty($p['image'])): ?>
                        <img class="product__img" src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                </a>
                <div class="product__body">
                    <span class="product__cat"><?= e($p['merchant'] ?? '') ?></span>
                    <h3 class="product__title">
                        <a href="<?= e(with_lang(url('pages/product.php?slug=' . urlencode($p['slug'])))) ?>"><?= e($p['name']) ?></a>
                    </h3>
                    <div class="product__foot">
                        <span class="product__price"><?= price_html($p['price'], $p['currency']) ?></span>
                        <a class="btn btn--primary product__buy" href="<?= e($p['url']) ?>" target="_blank" rel="sponsored nofollow noopener"><?= e(t('shop_buy')) ?> ↗</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

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
