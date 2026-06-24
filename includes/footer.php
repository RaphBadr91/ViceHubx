</main>

<footer class="site-footer">
    <div class="footer-glow" aria-hidden="true"></div>
    <div class="footer-inner">
        <div class="footer-brand">
            <a class="logo" href="<?= e(with_lang(url('index.php'))) ?>">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></a>
            <p class="footer-tag"><?= e(lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN) ?></p>
            <div class="footer-social" aria-label="Réseaux sociaux">
                <a href="#" aria-label="X / Twitter" title="X">𝕏</a>
                <a href="#" aria-label="YouTube" title="YouTube">▶</a>
                <a href="#" aria-label="Instagram" title="Instagram">📸</a>
                <a href="#" aria-label="Discord" title="Discord">💬</a>
                <a href="#" aria-label="TikTok" title="TikTok">♪</a>
            </div>
            <p class="footer-countdown">🕒 <b>GTA VI</b> — <?= e(release_human()) ?></p>
        </div>

        <nav class="footer-links" aria-label="Explorer">
            <h4><?= lang() === 'fr' ? 'Explorer' : 'Explore' ?></h4>
            <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('nav_news')) ?></a>
            <a href="<?= e(with_lang(url('pages/blog.php'))) ?>">Blog</a>
            <a href="<?= e(with_lang(url('pages/guides.php'))) ?>"><?= e(t('nav_guides')) ?></a>
            <a href="<?= e(with_lang(url('pages/leaks-lab.php'))) ?>"><?= e(t('nav_leaks')) ?></a>
            <a href="<?= e(with_lang(url('pages/trailer-lab.php'))) ?>"><?= e(t('nav_trailer')) ?></a>
        </nav>

        <nav class="footer-links" aria-label="Univers">
            <h4><?= lang() === 'fr' ? 'Univers' : 'Universe' ?></h4>
            <a href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('nav_map')) ?></a>
            <a href="<?= e(with_lang(url('pages/vehicles.php'))) ?>"><?= e(t('nav_vehicles')) ?></a>
            <a href="<?= e(with_lang(url('pages/characters.php'))) ?>"><?= e(t('nav_characters')) ?></a>
            <a href="<?= e(with_lang(url('pages/forum.php'))) ?>">Forum</a>
            <a href="<?= e(with_lang(url('pages/community.php'))) ?>"><?= e(t('nav_community')) ?></a>
        </nav>

        <nav class="footer-links" aria-label="Boutique">
            <h4><?= e(t('nav_shop')) ?></h4>
            <a href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'La Boutique' : 'The Shop' ?></a>
            <a href="<?= e(with_lang(url('pages/shop.php?cat=wallpaper'))) ?>">Wallpapers</a>
            <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= e(t('nav_deals')) ?></a>
        </nav>

        <nav class="footer-links" aria-label="ViceHub X">
            <h4>ViceHub X</h4>
            <?php if (is_logged_in()): ?>
                <a href="<?= e(with_lang(url('pages/account.php'))) ?>"><?= lang() === 'fr' ? 'Mon compte' : 'My account' ?></a>
            <?php else: ?>
                <a href="<?= e(with_lang(url('pages/register.php'))) ?>"><?= lang() === 'fr' ? 'Créer un compte' : 'Sign up' ?></a>
            <?php endif; ?>
            <a href="<?= e(with_lang(url('pages/contact.php'))) ?>"><?= e(t('nav_contact')) ?></a>
            <a href="<?= e(with_lang(url('pages/legal.php'))) ?>"><?= e(t('page_legal_title')) ?></a>
        </nav>
    </div>

    <div class="footer-bottom">
        <p class="disclaimer"><?= e(t('legal_disclaimer')) ?></p>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · <?= e(t('footer_rights')) ?> · <span class="footer-heart">fait avec 🩷 par des fans</span></p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/vicefm.js')) ?>" defer></script>
</body>
</html>
