<?php
/**
 * Editorial launch desk. A calendar date alone is not proof of availability.
 * Update $modDate only after checking the sources below.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$self = rtrim(BASE_URL, '/') . '/gta-6-launch-live';
$CANONICAL = $self . ($fr ? '' : '?lang=en');
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$modDate = '2026-10-30';
$launchDayReached = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d') >= '2026-11-19';
$SEO_TITLE = $fr ? 'GTA 6 est-il sorti ? Dates, préchargement et sources' : 'Is GTA 6 Out Yet? Release, Preload and Sources';
$SEO_DESC = $fr
    ? 'Sortie prévue le 19 novembre 2026 sur PS5 et Xbox Series X|S. Préchargement, accès au jeu et vérification par boutique : le point sourcé.'
    : 'Scheduled for November 19, 2026 on PS5 and Xbox Series X|S. A sourced checklist separating preload, purchase and access to the game.';
$official = 'https://www.rockstargames.com/VI';
$store = 'https://store.rockstargames.com/game/buy-gta-vi';
$ps = 'https://store.playstation.com/en-us/product/EP1004-PPSA01547_00-GTAVISTANDARD001/';
$faq = $fr ? [
    ['GTA 6 est-il déjà sorti ?', $launchDayReached
        ? 'La date annoncée est atteinte. Cette page ne certifie pas automatiquement la disponibilité : vérifiez la fiche officielle et votre bibliothèque, pour votre région et votre plateforme.'
        : 'Non au dernier contrôle éditorial du 30 octobre 2026. Rockstar annonce le 19 novembre 2026 sur PS5 et Xbox Series X|S.'],
    ['Préchargement et sortie sont-ils la même chose ?', 'Non. Le préchargement annoncé à partir du 12 novembre permet de télécharger les fichiers avant la sortie du 19 novembre. Il ne constitue pas un accès anticipé.'],
    ['À quelle heure le jeu sera-t-il jouable ?', 'Consultez la fiche du magasin correspondant à votre plateforme et votre région. Ne transposez pas une heure de préchargement ou une fiche américaine à toutes les régions.'],
    ['La version PC a-t-elle une date ?', 'Les sources officielles consultées pour cette fiche présentent PS5 et Xbox Series X|S. Aucune date PC n’est confirmée dans ces sources.'],
    ['Faut-il un nouveau SSD ou un patch ?', 'Vérifiez l’espace demandé par votre console et les mises à jour effectivement proposées. Une extension de stockage n’est pas un achat obligatoire et cette fiche ne présume pas la taille d’un éventuel patch.'],
] : [
    ['Is GTA 6 out yet?', $launchDayReached
        ? 'The announced date has been reached. This page does not automatically certify availability: check the official listing and your library for your platform and region.'
        : 'Not at our last editorial check on October 30, 2026. Rockstar lists November 19, 2026 for PS5 and Xbox Series X|S.'],
    ['Is preloading the same as release?', 'No. Preloading, announced from November 12, downloads files before the November 19 release. It does not grant early access.'],
    ['What time can I play?', 'Check the store listing for your platform and region. Do not apply a preload time or a US listing to every territory.'],
    ['Is there a confirmed PC date?', 'The official sources checked for this page list PS5 and Xbox Series X|S. They do not confirm a PC release date.'],
    ['Do I need a new SSD or a patch?', 'Check the storage requirement shown on your console and any updates actually offered. Extra storage is not a mandatory purchase; this page does not assume a patch size.'],
];
$JSONLD = ['@context'=>'https://schema.org', '@type'=>'Article',
    'headline'=>$SEO_TITLE, 'description'=>$SEO_DESC, 'inLanguage'=>lang(),
    'datePublished'=>'2026-10-17', 'dateModified'=>$modDate,
    'mainEntityOfPage'=>$CANONICAL, 'citation'=>[$official,$store,$ps]];
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow">ViceHub X · <?= $fr ? 'Suivi du lancement' : 'Launch desk' ?></span>
    <h1><?= e($SEO_TITLE) ?></h1>
    <p class="muted"><?= $fr ? 'Dernière vérification éditoriale : 30 octobre 2026.' : 'Last editorial verification: October 30, 2026.' ?></p>
    <div class="lore-block glass">
        <p><strong><?= e($faq[0][1]) ?></strong></p>
        <p><?= $fr ? 'Média fan indépendant, non affilié à Rockstar Games. La date prévue ne vaut pas confirmation d’accès dans toutes les régions.' : 'Independent fan publication, not affiliated with Rockstar Games. The scheduled date is not confirmation of access in every region.' ?></p>
    </div>
    <h2><?= $fr ? 'Trois étapes à ne pas confondre' : 'Three different milestones' ?></h2>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead><tr><th scope="col"><?= $fr ? 'Étape' : 'Step' ?></th><th scope="col"><?= $fr ? 'Ce que cela signifie' : 'What it means' ?></th><th scope="col">Source</th></tr></thead>
        <tbody>
        <tr><th scope="row"><?= $fr ? 'Achat / précommande' : 'Purchase / preorder' ?></th><td><?= $fr ? 'Vérifier plateforme, région, édition, prix et conditions du vendeur.' : 'Check platform, region, edition, price and seller terms.' ?></td><td><a href="<?= e($store) ?>">Rockstar Store</a></td></tr>
        <tr><th scope="row"><?= $fr ? 'Préchargement : 12 novembre' : 'Preload: November 12' ?></th><td><?= $fr ? 'Téléchargement anticipé ; ne signifie pas que le jeu est jouable.' : 'Download ahead of release; this does not mean the game is playable.' ?></td><td><a href="<?= e($store) ?>">Rockstar Store</a></td></tr>
        <tr><th scope="row"><?= $fr ? 'Sortie prévue : 19 novembre' : 'Scheduled release: November 19' ?></th><td><?= $fr ? 'PS5 et Xbox Series X|S ; vérifier l’heure sur la boutique de sa région.' : 'PS5 and Xbox Series X|S; check your regional store for the unlock time.' ?></td><td><a href="<?= e($official) ?>">Rockstar Games</a></td></tr>
        </tbody>
    </table>
    </div>
    <h2><?= $fr ? 'Checklist sans achat inutile' : 'A checklist without unnecessary purchases' ?></h2>
    <ol>
        <li><?= $fr ? 'Ouvrir la fiche officielle depuis son propre compte et relever la région.' : 'Open the official listing in your own account and note its region.' ?></li>
        <li><?= $fr ? 'Vérifier le contenu de l’édition et les conditions de renouvellement de tout abonnement inclus.' : 'Check edition contents and renewal terms for any included subscription.' ?></li>
        <li><?= $fr ? 'Lire le besoin de stockage affiché par la console avant d’acheter une extension.' : 'Read the storage requirement on the console before buying an expansion.' ?></li>
        <li><?= $fr ? 'Distinguer fichiers téléchargés, éventuelle mise à jour et lancement effectif du jeu.' : 'Distinguish downloaded files, any update and actually launching the game.' ?></li>
        <li><?= $fr ? 'Pour un signalement, préciser plateforme, pays, édition, heure et fuseau ; masquer les données du compte.' : 'When reporting availability, include platform, country, edition, time and timezone; hide account details.' ?></li>
    </ol>
    <h2><?= $fr ? 'Sources et limites' : 'Sources and limitations' ?></h2>
    <ul><li><a href="<?= e($official) ?>">Rockstar Games — GTA VI</a></li>
        <li><a href="<?= e($store) ?>">Rockstar Store — GTA VI</a></li>
        <li><a href="<?= e($ps) ?>">PlayStation Store — US</a> (<?= $fr ? 'cette région uniquement' : 'this region only' ?>)</li></ul>
    <p class="muted"><?= $fr ? 'Cette fiche est une synthèse éditoriale, pas un système de surveillance des serveurs ni une preuve d’accès au jeu. Les sources officielles et la bibliothèque de votre console priment.' : 'This is an editorial summary, not a server monitor or proof of game access. Official sources and your console library take precedence.' ?></p>
    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?><div class="lore-block glass"><h3><?= e($qa[0]) ?></h3><p><?= e($qa[1]) ?></p></div><?php endforeach; ?>
    <p><a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'Date de sortie' : 'Release date' ?></a> ·
    <a href="<?= e(with_lang(url('pages/gta-6-prix-editions.php'))) ?>"><?= $fr ? 'Prix et éditions' : 'Prices and editions' ?></a> ·
    <a href="<?= e(with_lang(url('pages/presse.php'))) ?>"><?= $fr ? 'Ressources presse' : 'Press resources' ?></a></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
