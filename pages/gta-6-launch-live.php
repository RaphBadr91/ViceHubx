<?php
/**
 * Pilier SEO « JOUR DE SORTIE » — suivi EN DIRECT de la sortie de GTA 6.
 * Cible les requêtes à très fort volume du lancement : « is GTA 6 out yet »,
 * « GTA 6 out now », « GTA 6 launch », « GTA 6 sortie en direct », « GTA 6 est
 * sorti ». Avant la sortie : compte à rebours + quoi faire le jour J + où
 * acheter (monétisation). Après la sortie : bascule en « C'EST SORTI ! » avec les
 * guides day-one. Bilingue FR/EN. Spoke du hub gta6.php.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-launch-live';

$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];

$deadline = release_date();                 // 2026-11-19 (compte à rebours partagé)
$relTs    = strtotime(RELEASE_DATE) ?: strtotime('2026-11-19');
$released  = time() >= $relTs;               // le jeu est-il sorti ?
$modDate  = date('Y-m-d');                   // fraîcheur : mis à jour chaque jour

$SEO_TITLE = $released
    ? ($fr ? 'GTA 6 est SORTI ! Suivi en direct & guides day-one' : 'GTA 6 Is OUT! Live Launch Tracker & Day-One Guides')
    : ($fr ? 'GTA 6 est-il sorti ? Compte à rebours en direct (19 nov. 2026)' : 'Is GTA 6 Out Yet? Live Countdown (Nov 19, 2026)');
$SEO_DESC = $released
    ? ($fr ? 'GTA 6 est disponible ! Suivi en direct de la sortie : serveurs, patch day-one, carte, soluces, astuces et où acheter au meilleur prix. Tout, minute par minute.'
           : 'GTA 6 is live! Real-time launch tracker: servers, day-one patch, map, walkthroughs, cheats and where to buy at the best price. Everything, minute by minute.')
    : ($fr ? 'GTA 6 est-il déjà sorti ? Pas encore : sortie le 19 novembre 2026 sur PS5 & Xbox. Compte à rebours en direct, ce qu\'il faut préparer et où précommander.'
           : 'Is GTA 6 out yet? Not yet: it launches November 19, 2026 on PS5 & Xbox. Live countdown, what to get ready and where to pre-order before the drop.');
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

$faq = $fr ? [
    ['GTA 6 est-il déjà sorti ?', $released
        ? 'Oui ! GTA 6 est disponible depuis le 19 novembre 2026 sur PS5 et Xbox Series X|S.'
        : 'Pas encore. GTA 6 sort le 19 novembre 2026 sur PS5 et Xbox Series X|S. Le compte à rebours en haut de page indique le temps restant exact, en direct.'],
    ['À quelle heure GTA 6 sort-il ?', 'Comme pour les précédents jeux Rockstar, le déblocage se fait en général à minuit heure locale sur consoles (parfois minuit heure de l\'Est aux États-Unis pour les éditions standard). On met l\'heure exacte à jour ici dès qu\'elle est confirmée.'],
    ['Sur quelles plateformes GTA 6 sort-il le jour J ?', 'PS5 et Xbox Series X|S uniquement. La version PC n\'a pas encore de date (attendue autour de 2027).'],
    ['Faut-il un patch day-one pour GTA 6 ?', 'Très probablement, comme pour tous les gros jeux Rockstar. Prévois de l\'espace de stockage libre et une bonne connexion pour le télécharger le jour de la sortie.'],
    ['Où acheter GTA 6 au meilleur prix le jour de la sortie ?', 'Compare les offres consoles, éditions et cartes cadeaux PSN/Xbox sur notre page bons plans, mise à jour en continu.'],
] : [
    ['Is GTA 6 out yet?', $released
        ? 'Yes! GTA 6 has been available since November 19, 2026 on PS5 and Xbox Series X|S.'
        : 'Not yet. GTA 6 launches November 19, 2026 on PS5 and Xbox Series X|S. The countdown at the top of this page shows the exact time left, live.'],
    ['What time does GTA 6 unlock?', 'Like previous Rockstar games, it usually unlocks at midnight local time on consoles (sometimes midnight ET in the US for standard editions). We update the exact time here as soon as it\'s confirmed.'],
    ['Which platforms does GTA 6 launch on?', 'PS5 and Xbox Series X|S only on day one. The PC version has no date yet (expected around 2027).'],
    ['Will GTA 6 need a day-one patch?', 'Almost certainly, as with every big Rockstar release. Make sure you have free storage space and a solid connection to download it on launch day.'],
    ['Where can I buy GTA 6 cheapest on launch day?', 'Compare console deals, editions and PSN/Xbox gift cards on our deals page, updated continuously.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        ['@type' => 'FAQPage', '@id' => $self . '#faq',
         'mainEntity' => array_map(static fn ($qa) => [
            '@type' => 'Question', 'name' => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
         ], $faq)],
        ['@type' => 'Article', '@id' => $self . '#article',
         'headline' => $SEO_TITLE, 'description' => $SEO_DESC, 'inLanguage' => lang(),
         'datePublished' => '2026-10-17', 'dateModified' => $modDate,
         'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'],
         'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow"><?= vhx_icon('live') ?> GTA 6 · <?= $fr ? 'Sortie en direct' : 'Launch live' ?></span>
    <h1><?= $released
        ? ($fr ? 'GTA 6 est SORTI ! 🎉' : 'GTA 6 Is OUT! 🎉')
        : ($fr ? 'GTA 6 est-il sorti ?' : 'Is GTA 6 Out Yet?') ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · <?= $fr ? 'suivi en direct' : 'live tracking' ?> · ViceHub X</p>

    <!-- Compte à rebours (bascule en "disponible" à la sortie via app.js) -->
    <div class="countdown" data-deadline="<?= e($deadline) ?>" role="timer" aria-label="<?= e(t('cd_title')) ?>" style="margin:1.2rem 0">
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

    <!-- Answer box (vise le featured snippet / AI Overview) -->
    <div class="lore-block glass" style="margin:1rem 0 1.6rem;border-left:3px solid <?= $released ? 'var(--pink,#ff2e88)' : 'var(--blue)' ?>">
        <p style="font-size:1.15rem;margin:0"><strong><?= $released
            ? ($fr ? 'C\'est officiel : GTA 6 est DISPONIBLE depuis le 19 novembre 2026 sur PS5 et Xbox Series X|S. Retrouve ci-dessous les guides du jour J, la carte, les astuces et où l\'acheter au meilleur prix.'
                   : 'It\'s official: GTA 6 is AVAILABLE as of November 19, 2026 on PS5 and Xbox Series X|S. Below: day-one guides, the map, cheats and where to buy it cheapest.')
            : ($fr ? 'Réponse courte : non, GTA 6 n\'est pas encore sorti. Sortie le 19 novembre 2026 sur PS5 et Xbox Series X|S. Le compte à rebours ci-dessus tourne en direct — reviens le jour J, cette page bascule automatiquement en suivi de sortie.'
                   : 'Short answer: no, GTA 6 isn\'t out yet. It launches November 19, 2026 on PS5 and Xbox Series X|S. The countdown above is live — come back on launch day, this page flips automatically into launch tracking.') ?></strong></p>
    </div>

    <?php if ($released): ?>
        <h2><?= $fr ? '🟢 En direct du lancement' : '🟢 Live from launch' ?></h2>
        <div class="lore-block glass">
            <ul style="margin:0">
                <li><?= $fr ? '<strong>Disponibilité :</strong> GTA 6 est jouable sur PS5 et Xbox Series X|S.' : '<strong>Availability:</strong> GTA 6 is playable on PS5 and Xbox Series X|S.' ?></li>
                <li><?= $fr ? '<strong>Patch day-one :</strong> pense à le télécharger avant de jouer.' : '<strong>Day-one patch:</strong> download it before you play.' ?></li>
                <li><?= $fr ? '<strong>Guides :</strong> nos soluces et astuces arrivent en continu ci-dessous.' : '<strong>Guides:</strong> our walkthroughs and tips are rolling out below.' ?></li>
            </ul>
        </div>
        <h2><?= $fr ? 'Les guides à ouvrir maintenant' : 'The guides to open now' ?></h2>
    <?php else: ?>
        <h2><?= $fr ? 'À préparer AVANT le jour J' : 'Get ready BEFORE launch day' ?></h2>
        <ul>
            <li><?= $fr ? '<strong>Libère de l\'espace de stockage</strong> (le jeu + le patch day-one seront lourds).' : '<strong>Free up storage space</strong> (the game + day-one patch will be large).' ?> <a href="<?= e(with_lang(url('article/gta-6-storage-requirements-space-needed-on-ps5-xbox-series-x-s'))) ?>"><?= $fr ? 'Combien d\'espace ?' : 'How much space?' ?></a></li>
            <li><?= $fr ? '<strong>Précommande</strong> pour jouer dès minuit et débloquer les bonus d\'édition.' : '<strong>Pre-order</strong> to play at midnight and unlock edition bonuses.' ?> <a href="<?= e(with_lang(url('pages/gta-6-prix-editions.php'))) ?>"><?= $fr ? 'Prix & éditions' : 'Prices & editions' ?></a></li>
            <li><?= $fr ? '<strong>Choisis ta console</strong> si tu n\'en as pas encore.' : '<strong>Pick your console</strong> if you don\'t have one yet.' ?> <a href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'Nos bons plans' : 'Our best deals' ?></a></li>
        </ul>
        <h2><?= $fr ? 'Le jour de la sortie, on sera là' : 'On launch day, we\'ll be here' ?></h2>
        <p class="muted"><?= $fr
            ? 'Cette page bascule automatiquement en suivi de sortie à minuit : disponibilité, patch day-one, carte complète, soluces et astuces. Ajoute-la à tes favoris.'
            : 'This page flips automatically into launch tracking at midnight: availability, day-one patch, full map, walkthroughs and cheats. Bookmark it.' ?></p>
    <?php endif; ?>

    <!-- Liens jour-J (existants) : poussent le crawl vers les pages à fort trafic -->
    <p class="muted">
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Dossier GTA 6 complet' : 'Full GTA 6 hub' ?></a> ·
        <a href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= $fr ? 'Carte de Leonida' : 'Leonida map' ?></a> ·
        <a href="<?= e(with_lang(url('pages/cheats.php'))) ?>"><?= $fr ? 'Astuces & codes' : 'Cheats & codes' ?></a> ·
        <a href="<?= e(with_lang(url('pages/guides.php'))) ?>"><?= $fr ? 'Soluces' : 'Walkthroughs' ?></a> ·
        <a href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Toute l\'actu' : 'All news' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'Date de sortie' : 'Release date' ?></a>
    </p>

    <!-- Où acheter (monétisation du pic de trafic) -->
    <div class="lore-block glass" style="margin:1.4rem 0;border-left:3px solid var(--pink,#ff2e88)">
        <h2 style="margin:0 0 .5rem"><?= $fr ? '🛒 Où acheter GTA 6 & le matériel' : '🛒 Where to buy GTA 6 & gear' ?></h2>
        <p class="muted" style="margin:0 0 .6rem"><?= $fr
            ? 'Consoles, manettes, casques, cartes PSN/Xbox et goodies GTA 6 — sélection mise à jour.'
            : 'Consoles, controllers, headsets, PSN/Xbox cards and GTA 6 goodies — curated and updated.' ?></p>
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/deals.php'))) ?>"><?= $fr ? 'Voir les bons plans' : 'See the deals' ?> →</a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= $fr ? 'La boutique ViceHub' : 'ViceHub store' ?></a>
    </div>

    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p class="muted" style="margin-top:1.6rem;font-size:.85rem"><?= $fr
        ? 'ViceHub X est un média indépendant non officiel. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two. Page mise à jour en continu autour de la sortie.'
        : 'ViceHub X is an independent, unofficial media. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two. Page continuously updated around launch.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
