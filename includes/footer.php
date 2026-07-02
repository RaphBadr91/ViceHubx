</main>

<footer class="site-footer">
    <div class="footer-glow" aria-hidden="true"></div>

    <!-- Bandeau d'appel à l'action -->
    <div class="footer-cta">
        <div class="footer-cta__txt">
            <h3><?= lang() === 'fr' ? 'Rejoins la communauté GTA6 n°1' : 'Join the #1 GTA6 community' ?></h3>
            <p class="muted"><?= lang() === 'fr' ? 'News, leaks, guides et débats entre passionnés de Vice City.' : 'News, leaks, guides and debates among Vice City fans.' ?></p>
        </div>
        <div class="footer-cta__btns">
            <a class="btn btn--primary" href="<?= e(with_lang(url('pages/forum.php'))) ?>">💬 <?= lang() === 'fr' ? 'Le Forum' : 'The Forum' ?></a>
            <?php if (!is_logged_in()): ?>
                <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/register.php'))) ?>"><?= lang() === 'fr' ? 'Créer un compte' : 'Sign up' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer-inner">
        <div class="footer-brand">
            <a class="logo" href="<?= e(with_lang(url('index.php'))) ?>">Vice<span class="logo-accent">Hub</span><span class="logo-x">X</span></a>
            <p class="footer-tag"><?= e(lang() === 'fr' ? APP_SLOGAN_FR : APP_SLOGAN_EN) ?></p>
            <div class="footer-social" aria-label="<?= lang() === 'fr' ? 'Réseaux sociaux' : 'Social media' ?>">
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
            <a href="<?= e(with_lang(url('pages/dossier.php'))) ?>"><?= lang() === 'fr' ? 'Le Dossier' : 'The Files' ?></a>
            <a href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= e(t('nav_map')) ?></a>
            <a href="<?= e(with_lang(url('pages/vehicles.php'))) ?>"><?= e(t('nav_vehicles')) ?></a>
            <a href="<?= e(with_lang(url('pages/characters.php'))) ?>"><?= e(t('nav_characters')) ?></a>
            <a href="<?= e(with_lang(url('pages/forum.php'))) ?>">Forum</a>
        </nav>

        <nav class="footer-links" aria-label="Boutique">
            <h4><?= e(t('nav_shop')) ?></h4>
            <a href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'La Boutique' : 'The Shop' ?></a>
            <a href="<?= e(with_lang(url('pages/shop.php?cat=wallpaper'))) ?>">Wallpapers</a>
            <a href="<?= e(with_lang(url('pages/quiz.php'))) ?>">Quiz</a>
            <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= e(t('nav_deals')) ?></a>
        </nav>

        <nav class="footer-links" aria-label="ViceHub X">
            <h4>ViceHub X</h4>
            <?php if (is_logged_in()): ?>
                <a href="<?= e(with_lang(url('pages/account.php'))) ?>"><?= lang() === 'fr' ? 'Mon compte' : 'My account' ?></a>
            <?php else: ?>
                <a href="<?= e(with_lang(url('pages/login.php'))) ?>"><?= lang() === 'fr' ? 'Connexion' : 'Login' ?></a>
            <?php endif; ?>
            <a href="<?= e(with_lang(url('pages/presse.php'))) ?>"><?= lang() === 'fr' ? 'Presse & Partenariats' : 'Press & Partners' ?></a>
            <a href="<?= e(with_lang(url('pages/contact.php'))) ?>"><?= e(t('nav_contact')) ?></a>
            <a href="<?= e(with_lang(url('pages/legal.php'))) ?>"><?= e(t('page_legal_title')) ?></a>
        </nav>
    </div>

    <div class="footer-trust">
        <span>🔒 <?= lang() === 'fr' ? 'Paiement sécurisé Stripe' : 'Secure Stripe payment' ?></span>
        <span>🌴 <?= lang() === 'fr' ? 'Média 100% fan' : '100% fan media' ?></span>
        <span>⚡ <?= lang() === 'fr' ? 'Mis à jour en continu' : 'Updated continuously' ?></span>
        <span>🌍 FR · EN</span>
    </div>

    <div class="footer-bottom">
        <p class="disclaimer"><?= e(t('legal_disclaimer')) ?></p>
        <div class="footer-bottom__row">
            <p class="footer-copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · <?= e(t('footer_rights')) ?></p>
            <a class="footer-top" href="#top"><?= lang() === 'fr' ? 'Haut de page' : 'Back to top' ?> ↑</a>
        </div>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/vicefm.js')) ?>" defer></script>
<script src="<?= e(asset('js/immersion.js')) ?>" defer></script>
<script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('<?= e(url('sw.js')) ?>').catch(function(){});});}</script>

<!-- Bandeau cookies (RGPD) -->
<div class="cookie-bar" id="cookieBar" hidden>
    <p><?= lang() === 'fr'
        ? '🍪 On utilise des cookies pour le bon fonctionnement du site et la mesure d’audience.'
        : '🍪 We use cookies for site functionality and analytics.' ?>
        <a href="<?= e(with_lang(url('pages/legal.php'))) ?>"><?= lang() === 'fr' ? 'En savoir plus' : 'Learn more' ?></a>
    </p>
    <div class="cookie-bar__btns">
        <button class="btn btn--ghost" data-cookie="refuse"><?= lang() === 'fr' ? 'Refuser' : 'Decline' ?></button>
        <button class="btn btn--primary" data-cookie="accept"><?= lang() === 'fr' ? 'Accepter' : 'Accept' ?></button>
    </div>
</div>
<script>
(function(){try{
  var bar=document.getElementById('cookieBar');
  if(!bar)return;
  // Masquage INFAILLIBLE : style inline display:none (l'emporte sur toute règle CSS).
  function hideBar(){bar.hidden=true;bar.style.display='none';}
  function showBar(){bar.hidden=false;bar.style.display='';}
  var v=null; try{v=localStorage.getItem('vhx_cookie');}catch(e){}
  if(v){hideBar();}else{showBar();}
  bar.querySelectorAll('[data-cookie]').forEach(function(b){
    b.addEventListener('click',function(){try{localStorage.setItem('vhx_cookie',b.getAttribute('data-cookie'));}catch(e){}hideBar();});
  });
}catch(e){}})();
</script>
</body>
</html>
