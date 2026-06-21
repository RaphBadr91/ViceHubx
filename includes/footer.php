</main>

<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-col footer-brand">
            <span class="logo">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></span>
            <p><?= e(lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN) ?></p>
        </div>
        <nav class="footer-col" aria-label="Footer">
            <h4><?= e(t('nav_news')) ?></h4>
            <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= e(t('nav_news')) ?></a>
            <a href="<?= e(with_lang(url('pages/guides.php'))) ?>"><?= e(t('nav_guides')) ?></a>
            <a href="<?= e(with_lang(url('pages/leaks-lab.php'))) ?>"><?= e(t('nav_leaks')) ?></a>
            <a href="<?= e(with_lang(url('pages/trailer-lab.php'))) ?>"><?= e(t('nav_trailer')) ?></a>
        </nav>
        <nav class="footer-col" aria-label="Footer explore">
            <h4><?= e(t('nav_map')) ?></h4>
            <a href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('nav_map')) ?></a>
            <a href="<?= e(with_lang(url('pages/vehicles.php'))) ?>"><?= e(t('nav_vehicles')) ?></a>
            <a href="<?= e(with_lang(url('pages/characters.php'))) ?>"><?= e(t('nav_characters')) ?></a>
            <a href="<?= e(with_lang(url('pages/community.php'))) ?>"><?= e(t('nav_community')) ?></a>
        </nav>
        <nav class="footer-col" aria-label="Footer legal">
            <h4>ViceHub X</h4>
            <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= e(t('nav_deals')) ?></a>
            <a href="<?= e(with_lang(url('pages/contact.php'))) ?>"><?= e(t('nav_contact')) ?></a>
            <a href="<?= e(with_lang(url('pages/legal.php'))) ?>"><?= e(t('page_legal_title')) ?></a>
        </nav>
    </div>

    <div class="footer-legal">
        <p class="disclaimer"><?= e(t('legal_disclaimer')) ?></p>
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. <?= e(t('footer_rights')) ?></p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
