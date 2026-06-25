<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = ($fr = lang() === 'fr') ? 'Le Dossier Vice City — l’encyclopédie GTA VI — ' . APP_NAME : 'The Vice City Files — GTA VI lore — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Tout l’univers de GTA VI réuni : Leonida, Vice City, quartiers, personnages, gangs, radios et chronologie de la saga Grand Theft Auto.'
    : 'The complete GTA VI universe: Leonida, Vice City, districts, characters, gangs, radio stations and the Grand Theft Auto timeline.';
$SEO_OG_IMAGE = cdn_url('downtown.png');

$JSONLD = [
    '@context' => 'https://schema.org',
    '@type'    => 'Article',
    'headline' => $fr ? 'Le Dossier Vice City — l’encyclopédie GTA VI' : 'The Vice City Files — GTA VI lore',
    'description' => $SEO_DESC,
    'author'   => ['@type' => 'Organization', 'name' => APP_NAME],
    'publisher'=> ['@type' => 'Organization', 'name' => APP_NAME],
    'inLanguage' => lang(),
];

$sections = [
    ['leonida',    '🌴', $fr ? 'Leonida & Vice City' : 'Leonida & Vice City'],
    ['quartiers',  '🏙️', $fr ? 'Les quartiers' : 'The districts'],
    ['persos',     '👥', $fr ? 'Les personnages' : 'The characters'],
    ['gangs',      '🔫', $fr ? 'Gangs & factions' : 'Gangs & factions'],
    ['radios',     '📻', $fr ? 'Les radios' : 'Radio stations'],
    ['chrono',     '🕹️', $fr ? 'Chronologie de la saga' : 'Series timeline'],
    ['lexique',    '📖', $fr ? 'Lexique GTA' : 'GTA glossary'],
    ['explorer',   '🧭', $fr ? 'Pour aller plus loin' : 'Dig deeper'],
];

// Articles à relier au dossier (maillage interne / SEO)
$dossier_cols = [
    ['news',     $fr ? '📰 Actus à la une'   : '📰 Top news'],
    ['guides',   $fr ? '📘 Guides essentiels' : '📘 Key guides'],
    ['leaks',    $fr ? '🕵️ Leaks décryptés'  : '🕵️ Leaks explained'],
    ['trailers', $fr ? '🎬 Analyses de trailers' : '🎬 Trailer breakdowns'],
];
$dossier_articles = [];
foreach ($dossier_cols as [$slug, $label]) {
    $dossier_articles[$slug] = get_articles(['category' => $slug, 'lang' => lang(), 'limit' => 5]);
}
require ROOT_PATH . '/includes/header.php';
?>
<section class="section dossier">
    <span class="eyebrow">📂 ViceHub X</span>
    <h1><?= $fr ? 'Le Dossier Vice City' : 'The Vice City Files' ?></h1>
    <p class="muted" style="max-width:760px"><?= $fr
        ? 'L’encyclopédie non officielle de GTA VI : tout ce qu’un amoureux de Vice City doit savoir, réuni au même endroit. Rumeurs et théories signalées comme telles.'
        : 'The unofficial GTA VI encyclopedia: everything a Vice City lover needs, in one place.' ?></p>

    <nav class="dossier-nav" aria-label="Sommaire">
        <?php foreach ($sections as [$id, $ico, $label]): ?>
            <a href="#<?= e($id) ?>"><?= $ico ?> <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <article class="dossier-body">
        <div class="lore-block glass" id="leonida">
            <h2>🌴 <?= $fr ? 'Leonida & Vice City' : 'Leonida & Vice City' ?></h2>
            <p><?= $fr
                ? 'GTA VI nous emmène dans l’État fictif de <strong>Leonida</strong>, inspiré de la Floride, dont la perle est <strong>Vice City</strong> — une métropole néon entre plages dorées, gratte-ciels Art déco et marécages sauvages. Météo dynamique, ouragans, foule réactive : la ville respire comme jamais dans la saga.'
                : 'GTA VI takes us to the fictional state of <strong>Leonida</strong>, inspired by Florida, whose jewel is <strong>Vice City</strong> — a neon metropolis of golden beaches, Art-deco towers and wild swamps.' ?></p>
            <p class="muted"><?= $fr ? 'À retrouver : ' : 'See also: ' ?>
                <a class="link-all" href="<?= e(with_lang(url('pages/map.php'))) ?>"><?= $fr ? 'la carte interactive' : 'the interactive map' ?></a>.</p>
        </div>

        <div class="lore-block glass" id="quartiers">
            <h2>🏙️ <?= $fr ? 'Les quartiers de Vice City' : 'Vice City districts' ?></h2>
            <div class="lore-grid">
                <div><h3>🏖️ Beachfront</h3><p class="muted"><?= $fr ? 'Plages, néons et vie nocturne. Le cœur touristique et festif.' : 'Beaches, neon and nightlife.' ?></p></div>
                <div><h3>🌆 Downtown</h3><p class="muted"><?= $fr ? 'Gratte-ciels, sièges sociaux et missions à gros enjeux.' : 'Skyscrapers and high-stakes missions.' ?></p></div>
                <div><h3>⛵ Marina</h3><p class="muted"><?= $fr ? 'Yachts, docks et planques côtières. Accès à la mer.' : 'Yachts, docks and coastal hideouts.' ?></p></div>
                <div><h3>🎶 Nightclub District</h3><p class="muted"><?= $fr ? 'Clubs, contacts et opportunités douteuses.' : 'Clubs, contacts and shady deals.' ?></p></div>
                <div><h3>🐊 Everglades</h3><p class="muted"><?= $fr ? 'Marécages sauvages, courses tout-terrain et secrets.' : 'Wild swamps and off-road racing.' ?></p></div>
                <div><h3>🌇 Art Deco Strip</h3><p class="muted"><?= $fr ? 'Hôtels pastel et voitures vintage. L’âme rétro de la ville.' : 'Pastel hotels and vintage cars.' ?></p></div>
            </div>
        </div>

        <div class="lore-block glass" id="persos">
            <h2>👥 <?= $fr ? 'Les personnages clés' : 'Key characters' ?></h2>
            <div class="lore-grid">
                <div><h3>Lucia</h3><p class="muted"><?= $fr ? 'Protagoniste. Ancienne détenue en quête d’un nouveau départ — une première féminine pour un rôle principal de la saga.' : 'Protagonist. A former inmate seeking a fresh start.' ?></p></div>
                <div><h3>Jason</h3><p class="muted"><?= $fr ? 'Protagoniste. Partenaire de Lucia, débrouillard et impulsif. Leur duo est au cœur de l’intrigue.' : 'Protagonist. Lucia’s partner, resourceful and impulsive.' ?></p></div>
                <div><h3>Le Maire</h3><p class="muted"><?= $fr ? 'Figure politique corrompue qui tire les ficelles de la ville (théorie communautaire).' : 'A corrupt political figure pulling the city’s strings (fan theory).' ?></p></div>
                <div><h3>DJ Solaris</h3><p class="muted"><?= $fr ? 'Voix culte des ondes de Vice City. Clin d’œil possible aux anciens opus.' : 'Cult voice of Vice City radio.' ?></p></div>
            </div>
            <p class="muted" style="margin-top:1rem"><a class="link-all" href="<?= e(with_lang(url('pages/characters.php'))) ?>"><?= $fr ? 'Fiches détaillées des personnages' : 'Full character sheets' ?> →</a></p>
        </div>

        <div class="lore-block glass" id="gangs">
            <h2>🔫 <?= $fr ? 'Gangs & factions' : 'Gangs & factions' ?></h2>
            <p class="muted" style="font-size:.85rem"><?= $fr ? '⚠️ Reconstitution communautaire à partir des indices des trailers — non officiel.' : '⚠️ Community reconstruction from trailer clues — unofficial.' ?></p>
            <div class="lore-grid">
                <div><h3>🌴 Cartels côtiers</h3><p class="muted"><?= $fr ? 'Contrôlent le trafic le long du littoral et des marinas.' : 'Control coastal smuggling routes.' ?></p></div>
                <div><h3>🏍️ Bikers des Everglades</h3><p class="muted"><?= $fr ? 'Règnent sur l’arrière-pays et les routes désertes.' : 'Rule the backcountry roads.' ?></p></div>
                <div><h3>💼 Cols blancs de Downtown</h3><p class="muted"><?= $fr ? 'Crime en costume : immobilier, blanchiment, politique.' : 'White-collar crime: real estate, laundering.' ?></p></div>
                <div><h3>🎰 Familles du jeu</h3><p class="muted"><?= $fr ? 'Casinos, paris et night-clubs sous influence.' : 'Casinos and nightlife under influence.' ?></p></div>
            </div>
        </div>

        <div class="lore-block glass" id="radios">
            <h2>📻 <?= $fr ? 'Les radios de Vice City' : 'Vice City radio' ?></h2>
            <p><?= $fr ? 'La bande-son fait l’identité de GTA. En attendant la playlist officielle, branche notre <strong>Vice FM</strong> (en bas à gauche 📻) pour une ambiance synthwave maison.' : 'The soundtrack is GTA’s soul. Tune into our <strong>Vice FM</strong> player (bottom-left 📻).' ?></p>
            <div class="lore-grid">
                <div><h3>Vice FM 99.7</h3><p class="muted">Synthwave</p></div>
                <div><h3>Wave 102</h3><p class="muted">Chillwave</p></div>
                <div><h3>Sunset 95</h3><p class="muted">Retro</p></div>
            </div>
        </div>

        <div class="lore-block glass" id="chrono">
            <h2>🕹️ <?= $fr ? 'Chronologie de la saga GTA' : 'GTA series timeline' ?></h2>
            <ul class="lore-timeline">
                <li><b>2002</b> — GTA: Vice City <span class="muted"><?= $fr ? '— les années 80, naissance du mythe néon.' : '— the neon ’80s.' ?></span></li>
                <li><b>2004</b> — GTA: San Andreas</li>
                <li><b>2008</b> — GTA IV <span class="muted">— Liberty City</span></li>
                <li><b>2013</b> — GTA V <span class="muted">— Los Santos</span></li>
                <li><b>2026</b> — <strong>GTA VI</strong> <span class="muted"><?= $fr ? '— retour à Vice City, le 19 novembre.' : '— back to Vice City, Nov 19.' ?></span></li>
            </ul>
        </div>

        <div class="lore-block glass" id="lexique">
            <h2>📖 <?= $fr ? 'Lexique GTA' : 'GTA glossary' ?></h2>
            <dl class="lore-dl">
                <dt>RAGE</dt><dd class="muted"><?= $fr ? 'Le moteur de jeu de Rockstar (physique, météo, IA).' : 'Rockstar’s game engine.' ?></dd>
                <dt>BAWSAQ</dt><dd class="muted"><?= $fr ? 'La bourse de l’univers GTA. Découvre notre ' : 'The GTA universe stock market. See our ' ?><a class="link-all" href="<?= e(with_lang(url('pages/bawsaq.php'))) ?>"><?= $fr ? 'BAWSAQ 2026' : 'BAWSAQ 2026' ?></a>.</dd>
                <dt><?= $fr ? 'Indice de recherche' : 'Wanted level' ?></dt><dd class="muted"><?= $fr ? 'Le niveau d’alerte de la police, en étoiles ⭐.' : 'Police alert level, in stars ⭐.' ?></dd>
                <dt>Leak</dt><dd class="muted"><?= $fr ? 'Fuite non confirmée. À prendre avec prudence.' : 'Unconfirmed leak. Handle with care.' ?></dd>
                <dt>Heist</dt><dd class="muted"><?= $fr ? 'Braquage scénarisé, souvent en plusieurs étapes.' : 'A scripted multi-step robbery.' ?></dd>
            </dl>
        </div>

        <div class="lore-block glass" id="explorer">
            <h2>🧭 <?= $fr ? 'Pour aller plus loin' : 'Dig deeper' ?></h2>
            <p class="muted"><?= $fr ? 'Nos derniers articles pour approfondir chaque thème du dossier.' : 'Our latest articles to dig into each topic.' ?></p>
            <div class="lore-links">
                <?php foreach ($dossier_cols as [$cslug, $clabel]): $list = $dossier_articles[$cslug] ?? []; if (!$list) continue; ?>
                    <div class="lore-links__col">
                        <h3><?= e($clabel) ?></h3>
                        <ul>
                            <?php foreach ($list as $a): ?>
                                <li><a href="<?= e(with_lang(url('pages/article.php?slug=' . urlencode($a['slug'])))) ?>"><?= e($a['title']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <a class="link-all" href="<?= e(with_lang(url('pages/' . ($cslug === 'leaks' ? 'leaks-lab' : ($cslug === 'trailers' ? 'trailer-lab' : $cslug)) . '.php'))) ?>"><?= $fr ? 'Tout voir' : 'See all' ?> →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </article>

    <div class="banner glass" style="text-align:center;margin-top:2rem">
        <h2><?= $fr ? 'Tu es plutôt Lucia ou Jason ?' : 'Are you Lucia or Jason?' ?></h2>
        <p class="muted"><?= $fr ? 'Fais le test en 1 minute.' : 'Take the 1-minute quiz.' ?></p>
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/quiz.php'))) ?>"><?= $fr ? 'Faire le quiz' : 'Take the quiz' ?> →</a>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
