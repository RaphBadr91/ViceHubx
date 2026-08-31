<?php
/**
 * Pilier SEO à intention UNIQUE — « prix GTA 6 / éditions / précommande ».
 * Answer-first + tableau comparatif (Standard vs Ultimate) + FAQPage schema.
 * Bilingue FR/EN. Spoke du hub gta6.php (liens réciproques → anti-cannibalisation).
 * Faits alignés sur gta6.php (canon du site) — aucune contradiction.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta-6-prix-editions';

$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$pubDate = '2026-08-31';
$modDate = '2026-08-31';

$SEO_TITLE = $fr
    ? 'Prix de GTA 6 et éditions : Standard ou Ultimate ?'
    : 'GTA 6 Price & Editions: Standard or Ultimate?';
$SEO_DESC = $fr
    ? 'Prix de GTA 6 : édition Standard à 79,99 € et Ultimate à 99,99 €. Comparatif clair des deux éditions, bonus de précommande (Vintage Vice City Pack) et laquelle choisir.'
    : 'GTA 6 price: Standard edition €79.99 and Ultimate €99.99. A clear comparison of both editions, the pre-order bonus (Vintage Vice City Pack) and which one to pick.';
$SEO_OG_IMAGE = cdn_url('downtown.png') ?: ($base . '/public/assets/img/brand/og-share.jpg');

// FAQ ciblée « prix / éditions » — reprise dans le schema ET affichée.
$faq = $fr ? [
    ['Combien coûte GTA 6 ?', 'GTA 6 coûte 79,99 € pour l\'édition Standard et 99,99 € pour l\'édition Ultimate (soit 79,99 $ et 99,99 $). Prix indicatifs, susceptibles de varier selon la région et la boutique.'],
    ['Quelle est la différence entre Standard et Ultimate ?', 'L\'édition Standard contient le jeu de base et le bonus de précommande Vintage Vice City. L\'édition Ultimate (99,99 €) ajoute des véhicules et armes exclusifs, des packs cosmétiques, des boutiques et un garage dédiés, une mission annexe et un mois de GTA+ sur les boutiques PlayStation et Xbox.'],
    ['Peut-on précommander GTA 6 et quels sont les bonus ?', 'Oui. La précommande donne accès au Vintage Vice City Pack (un véhicule, un garage et des cosmétiques pour Jason et Lucia), inclus d\'office dans l\'édition Ultimate. Les bonus et dates limites peuvent varier selon la boutique.'],
    ['Quelle édition de GTA 6 choisir ?', 'Si vous voulez juste jouer, la Standard (79,99 €) suffit largement. L\'Ultimate (99,99 €) s\'adresse aux fans qui veulent le contenu bonus et un mois de GTA+ ; c\'est surtout intéressant si vous comptez jouer à GTA Online dès le lancement.'],
    ['GTA 6 sera-t-il inclus dans le Game Pass ou le PS Plus au lancement ?', 'Non annoncé, et très improbable. Rockstar n\'a jamais mis un GTA dans un abonnement le jour de sa sortie. Il faudra très probablement acheter le jeu au lancement.'],
    ['Y a-t-il une édition collector physique de GTA 6 ?', 'À ce jour, aucune édition collector physique n\'a été annoncée. L\'édition Standard existe en boîte, mais celle-ci contient un code de téléchargement plutôt qu\'un disque dans certaines régions.'],
] : [
    ['How much does GTA 6 cost?', 'GTA 6 costs €79.99 for the Standard edition and €99.99 for the Ultimate edition ($79.99 and $99.99). Prices are indicative and may vary by region and store.'],
    ['What\'s the difference between Standard and Ultimate?', 'The Standard edition includes the base game and the Vintage Vice City pre-order bonus. The Ultimate edition (€99.99) adds exclusive vehicles and weapons, cosmetic packs, dedicated shops and a garage, a side mission, and one month of GTA+ on the PlayStation and Xbox stores.'],
    ['Can I pre-order GTA 6 and what are the bonuses?', 'Yes. Pre-ordering grants the Vintage Vice City Pack (a vehicle, garage and cosmetics for Jason and Lucia), included by default in the Ultimate edition. Bonuses and deadlines may vary by store.'],
    ['Which GTA 6 edition should I choose?', 'If you just want to play, Standard (€79.99) is plenty. Ultimate (€99.99) is for fans who want the bonus content and a month of GTA+ — mostly worth it if you plan to jump into GTA Online at launch.'],
    ['Will GTA 6 be on Game Pass or PS Plus at launch?', 'Not announced, and highly unlikely. Rockstar has never put a GTA into a subscription on release day. You\'ll almost certainly need to buy the game at launch.'],
    ['Is there a physical collector\'s edition of GTA 6?', 'As of now, no physical collector\'s edition has been announced. The Standard edition exists as a box, but in some regions it contains a download code rather than a disc.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type' => 'FAQPage',
            '@id'   => $self . '#faq',
            'mainEntity' => array_map(static fn ($qa) => [
                '@type' => 'Question', 'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faq),
        ],
        [
            '@type' => 'Article',
            '@id'   => $self . '#article',
            'headline' => $SEO_TITLE,
            'description' => $SEO_DESC,
            'inLanguage' => lang(),
            'datePublished' => $pubDate,
            'dateModified'  => $modDate,
            'author'    => ['@id' => $base . '/#org'],
            'publisher' => ['@id' => $base . '/#org'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self],
        ],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow">💸 GTA 6 · <?= $fr ? 'Prix &amp; éditions' : 'Price &amp; editions' ?></span>
    <h1><?= $fr ? 'Prix de GTA 6 et éditions' : 'GTA 6 Price &amp; Editions' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>

    <!-- Answer box : réponse directe (featured snippet / AI Overview) -->
    <div class="lore-block glass" style="margin:1rem 0 1.6rem;border-left:3px solid var(--blue)">
        <p style="font-size:1.15rem;margin:0"><strong><?= $fr
            ? 'En résumé : GTA 6 coûte 79,99 € (édition Standard) ou 99,99 € (édition Ultimate). Sortie le 19 novembre 2026 sur PS5 et Xbox Series X|S. La précommande offre le Vintage Vice City Pack.'
            : 'Short answer: GTA 6 costs €79.99 (Standard edition) or €99.99 (Ultimate edition). Launching November 19, 2026 on PS5 and Xbox Series X|S. Pre-ordering grants the Vintage Vice City Pack.' ?></strong></p>
    </div>

    <h2><?= $fr ? 'Comparatif des éditions : Standard vs Ultimate' : 'Edition comparison: Standard vs Ultimate' ?></h2>
    <div style="overflow-x:auto;margin:1rem 0">
        <table style="width:100%;border-collapse:collapse;min-width:520px">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--blue)">
                    <th style="padding:.6rem .5rem"><?= $fr ? 'Contenu' : 'Content' ?></th>
                    <th style="padding:.6rem .5rem">Standard</th>
                    <th style="padding:.6rem .5rem">Ultimate</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = $fr ? [
                    ['Prix', '79,99 € / 79,99 $', '99,99 € / 99,99 $'],
                    ['Format', 'Numérique ou boîte', 'Numérique uniquement'],
                    ['Le jeu GTA 6 complet', '✅', '✅'],
                    ['Bonus précommande Vintage Vice City', '✅', '✅'],
                    ['Véhicules &amp; armes exclusifs', '—', '✅'],
                    ['Packs cosmétiques', '—', '✅'],
                    ['Boutiques &amp; garage dédiés', '—', '✅'],
                    ['Mission annexe exclusive', '—', '✅'],
                    ['1 mois de GTA+ (PS/Xbox)', '—', '✅'],
                ] : [
                    ['Price', '€79.99 / $79.99', '€99.99 / $99.99'],
                    ['Format', 'Digital or boxed', 'Digital only'],
                    ['The full GTA 6 game', '✅', '✅'],
                    ['Vintage Vice City pre-order bonus', '✅', '✅'],
                    ['Exclusive vehicles &amp; weapons', '—', '✅'],
                    ['Cosmetic packs', '—', '✅'],
                    ['Dedicated shops &amp; garage', '—', '✅'],
                    ['Exclusive side mission', '—', '✅'],
                    ['1 month of GTA+ (PS/Xbox)', '—', '✅'],
                ];
                foreach ($rows as $i => $r): ?>
                    <tr style="border-bottom:1px solid var(--glass-brd,rgba(255,255,255,.08))">
                        <td style="padding:.55rem .5rem"><?= $r[0] ?></td>
                        <td style="padding:.55rem .5rem"><?= $r[1] ?></td>
                        <td style="padding:.55rem .5rem"><?= $r[2] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2><?= $fr ? 'Quelle édition GTA 6 choisir ?' : 'Which GTA 6 edition should you pick?' ?></h2>
    <p class="muted"><?= $fr
        ? 'Pour la plupart des joueurs, l\'<strong>édition Standard à 79,99 €</strong> est le bon choix : elle contient l\'intégralité du jeu et le bonus de précommande. L\'<strong>édition Ultimate à 99,99 €</strong> n\'a de sens que si vous voulez le contenu cosmétique exclusif, la mission annexe et le mois de GTA+ offert — surtout pertinent si vous comptez vous lancer dans GTA Online dès le premier jour.'
        : 'For most players, the <strong>Standard edition at €79.99</strong> is the right call: it includes the entire game and the pre-order bonus. The <strong>Ultimate edition at €99.99</strong> only makes sense if you want the exclusive cosmetic content, the side mission and the free month of GTA+ — especially worth it if you plan to jump into GTA Online on day one.' ?></p>

    <h2><?= $fr ? 'Précommande &amp; bonus' : 'Pre-order &amp; bonus' ?></h2>
    <p class="muted"><?= $fr
        ? 'La précommande de GTA 6 donne accès au <strong>Vintage Vice City Pack</strong> : un véhicule, un garage et des cosmétiques pour Jason et Lucia. Ce pack est inclus d\'office dans l\'édition Ultimate. Les bonus, prix et dates limites peuvent varier selon la boutique (PlayStation Store, Xbox Store, revendeurs).'
        : 'Pre-ordering GTA 6 grants the <strong>Vintage Vice City Pack</strong>: a vehicle, a garage and cosmetics for Jason and Lucia. This pack is included by default in the Ultimate edition. Bonuses, prices and deadlines may vary by store (PlayStation Store, Xbox Store, retailers).' ?></p>
    <blockquote class="lore-block glass" style="border-left:3px solid var(--pink,#ff2e88)"><?= $fr
        ? '✅ <strong>Confirmé :</strong> deux éditions (79,99 € / 99,99 €), bonus Vintage Vice City. &nbsp; ❔ <strong>À confirmer :</strong> tarifs régionaux exacts et dates d\'ouverture des précommandes selon les boutiques.'
        : '✅ <strong>Confirmed:</strong> two editions (€79.99 / €99.99), Vintage Vice City bonus. &nbsp; ❔ <strong>To confirm:</strong> exact regional pricing and pre-order opening dates per store.' ?></blockquote>

    <h2><?= $fr ? 'En attendant, habille-toi Vice City' : 'In the meantime, dress Vice City' ?></h2>
    <p class="muted"><?= $fr
        ? 'On ne vend pas le jeu (on est un média fan indépendant), mais notre boutique propose du merch et des wallpapers HD à l\'esprit Vice City.'
        : 'We don\'t sell the game (we\'re an independent fan media), but our shop has Vice City-inspired merch and HD wallpapers.' ?>
        <a href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= $fr ? 'Voir la boutique' : 'Browse the shop' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta-6-date-de-sortie.php'))) ?>"><?= $fr ? 'Date de sortie' : 'Release date' ?></a> ·
        <a href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Le dossier GTA 6 complet' : 'The full GTA 6 hub' ?></a>.
    </p>

    <h2><?= $fr ? 'Questions fréquentes' : 'Frequently asked questions' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p class="muted" style="margin-top:1.6rem;font-size:.85rem"><?= $fr
        ? 'Tarifs indicatifs, susceptibles de varier selon la région et la boutique. ViceHub X est un média indépendant non officiel et ne vend pas le jeu. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two.'
        : 'Indicative prices, subject to change by region and store. ViceHub X is an independent, unofficial media and does not sell the game. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two.' ?></p>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
