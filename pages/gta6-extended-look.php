<?php
/**
 * TOP NEWS — « GTA VI: An Extended Look » (gameplay en avant-première Netflix
 * le 27 août 2026, puis gratuit sur YouTube). Page événement + compte à rebours.
 * Faits vérifiés (Rockstar Newswire + presse) ; durée présentée comme non confirmée.
 */
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'vicehubx.com');
$self   = $base . '/gta6-extended-look';

$CANONICAL    = (lang() === 'en') ? $self . '?lang=en' : $self;
$HREFLANG_ALT = ['fr' => $self, 'en' => $self . '?lang=en'];
$pubDate = '2026-08-12';
$modDate = '2026-08-12';

// Instants ABSOLUS (UTC) — le JS convertit à l'heure locale du visiteur.
$NETFLIX_UTC = '2026-08-27T19:00:00Z'; // 21h Paris / 3pm ET / 12pm PT / 8pm BST
$YOUTUBE_UTC = '2026-08-28T01:00:00Z'; // 3h Paris (nuit du 27 au 28)

$SEO_TITLE = $fr
    ? 'GTA 6 : gameplay en avant-première Netflix le 27 août (comment regarder)'
    : 'GTA 6 Gameplay Reveal on Netflix Aug 27: How & When to Watch';
$SEO_DESC = $fr
    ? 'Rockstar diffuse « GTA VI: An Extended Look » le 27 août 2026 : 21h en exclu Netflix, puis GRATUIT sur YouTube à 3h du matin. Horaires, durée et comment regarder.'
    : 'Rockstar airs "GTA VI: An Extended Look" on August 27, 2026: 9pm CEST Netflix-exclusive, then FREE on YouTube. Times, runtime and how to watch.';
// Image de l'événement : réglable dans Admin → Réglages (URL à coller OU upload).
// Repli sur une scène par défaut si rien n'est défini.
$__evImg = trim((string) get_setting('event_image', ''));
$eventImgSrc = $__evImg !== ''
    ? (preg_match('#^https?://#', $__evImg) ? $__evImg : img_src($__evImg))
    : (cdn_url('downtown.png') ?: asset('img/scenes/nightlife.png'));
$SEO_OG_IMAGE = $eventImgSrc;

$faq = $fr ? [
    ['Quand sort le gameplay de GTA 6 ?', 'Le 27 août 2026. « GTA VI: An Extended Look » est diffusé d\'abord en exclusivité sur Netflix à 21h00 (heure de Paris), puis gratuitement sur la chaîne YouTube de Rockstar et le site officiel GTA VI vers 3h00 du matin (nuit du 27 au 28 août).'],
    ['Comment regarder le gameplay de GTA 6 gratuitement ?', 'Sans abonnement Netflix, il suffit d\'attendre la sortie gratuite sur YouTube (chaîne officielle Rockstar Games) et sur le site GTA VI, environ 6 heures après la première Netflix, soit vers 3h du matin heure de Paris.'],
    ['Faut-il Netflix pour voir le gameplay de GTA 6 ?', 'Uniquement pour le voir en avant-première à 21h. Netflix a une fenêtre d\'exclusivité d\'environ 6 heures ; ensuite la vidéo est disponible gratuitement pour tout le monde sur YouTube et le site officiel.'],
    ['Combien de temps dure le gameplay de GTA 6 ?', 'La durée n\'est pas officiellement confirmée par Rockstar. Selon des fuites (support client Netflix), la vidéo durerait environ 20 minutes — bien plus que les trailers de gameplay habituels (4 à 6 min). À prendre avec prudence tant que Rockstar ne l\'a pas confirmé.'],
    ['GTA 6 est-il repoussé ?', 'Non. La sortie reste fixée au 19 novembre 2026 sur PS5 et Xbox Series X|S. Cet événement est une présentation de gameplay, pas une annonce de report.'],
] : [
    ['When is the GTA 6 gameplay reveal?', 'August 27, 2026. "GTA VI: An Extended Look" premieres exclusively on Netflix at 3pm ET / 9pm CEST, then goes free on Rockstar\'s YouTube channel and the official GTA VI website at 9pm ET (around 6 hours later).'],
    ['How can I watch the GTA 6 gameplay for free?', 'Without Netflix, just wait for the free release on Rockstar Games\' official YouTube channel and the GTA VI website, roughly 6 hours after the Netflix premiere (9pm ET / 3am CEST).'],
    ['Do I need Netflix to watch the GTA 6 reveal?', 'Only to watch the early premiere at 3pm ET. Netflix has an ~6-hour exclusivity window; after that the video is available for free to everyone on YouTube and the official site.'],
    ['How long is the GTA 6 Extended Look?', 'The runtime is not officially confirmed by Rockstar. Leaks (a Netflix support agent) suggest around 20 minutes — far longer than typical gameplay trailers (4-6 min). Treat it with caution until Rockstar confirms.'],
    ['Is GTA 6 delayed?', 'No. The release stays set for November 19, 2026 on PS5 and Xbox Series X|S. This event is a gameplay showcase, not a delay announcement.'],
];

$JSONLD = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        ['@type' => 'FAQPage', '@id' => $self . '#faq',
         'mainEntity' => array_map(static fn ($qa) => [
             '@type' => 'Question', 'name' => $qa[0],
             'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
         ], $faq)],
        ['@type' => 'NewsArticle', '@id' => $self . '#article',
         'headline' => $SEO_TITLE, 'description' => $SEO_DESC, 'inLanguage' => lang(),
         'datePublished' => $pubDate, 'dateModified' => $modDate,
         'author' => ['@id' => $base . '/#org'], 'publisher' => ['@id' => $base . '/#org'],
         'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $self]],
        ['@type' => 'Event', '@id' => $self . '#event',
         'name' => 'Grand Theft Auto VI: An Extended Look',
         'startDate' => $NETFLIX_UTC,
         'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
         'eventStatus' => 'https://schema.org/EventScheduled',
         'location' => ['@type' => 'VirtualLocation', 'url' => 'https://www.netflix.com/'],
         'description' => $SEO_DESC],
    ],
];

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:900px">
    <span class="eyebrow" style="color:var(--pink)"><?= vhx_icon('live') ?> <?= $fr ? 'ÉVÉNEMENT · GTA 6' : 'BREAKING · GTA 6' ?></span>
    <h1><?= $fr ? 'GTA 6 : gameplay en avant-première Netflix le 27 août' : 'GTA 6 Gameplay Reveal on Netflix — August 27' ?></h1>
    <p class="muted" style="margin:.2rem 0 0;font-size:.85rem"><?= $fr ? 'Mis à jour le' : 'Updated' ?> <?= e(date($fr ? 'd/m/Y' : 'M j, Y', strtotime($modDate) ?: time())) ?> · ViceHub X</p>

    <img src="<?= e($eventImgSrc) ?>" alt="<?= e($fr ? 'GTA 6 — gameplay en avant-première Netflix' : 'GTA 6 — gameplay premiere on Netflix') ?>" style="width:100%;border-radius:16px;margin:1.1rem 0 .4rem;aspect-ratio:16/9;object-fit:cover" loading="eager">

    <div class="lore-block glass" style="margin:1rem 0 1.4rem;border-left:3px solid var(--pink)">
        <p style="font-size:1.15rem;margin:0"><strong><?= $fr
            ? 'Rockstar diffuse « GTA VI: An Extended Look », une présentation de gameplay commentée, le 27 août 2026 : d\'abord en EXCLUSIVITÉ sur Netflix à 21h00 (heure de Paris), puis GRATUITEMENT sur la chaîne YouTube de Rockstar et le site GTA VI vers 3h00 du matin (nuit du 27 au 28).'
            : 'Rockstar airs "GTA VI: An Extended Look", a narrated gameplay showcase, on August 27, 2026: first EXCLUSIVELY on Netflix at 3pm ET / 9pm CEST, then FREE on Rockstar\'s YouTube channel and the GTA VI site at 9pm ET.' ?></strong></p>
    </div>

    <!-- Compte à rebours live (heure locale du visiteur) -->
    <div class="lore-block glass" style="text-align:center" id="vhx-cd" data-netflix="<?= e($NETFLIX_UTC) ?>" data-youtube="<?= e($YOUTUBE_UTC) ?>">
        <div class="muted" style="text-transform:uppercase;letter-spacing:.08em;font-size:.8rem" data-cd-label><?= $fr ? 'Avant-première Netflix dans' : 'Netflix premiere in' ?></div>
        <div style="font-family:var(--font-display);font-size:2rem;font-weight:800;margin:.3rem 0" data-cd-timer>—</div>
        <div class="muted" style="font-size:.85rem" data-cd-sub></div>
    </div>

    <h2>📺 <?= $fr ? 'Comment et quand regarder' : 'How & when to watch' ?></h2>
    <div class="lore-block glass">
        <ul style="margin:0">
            <li><strong>🔴 <?= $fr ? 'Netflix (avant-première, abonnement requis)' : 'Netflix (early premiere, subscription required)' ?></strong> — <?= $fr ? '27 août, <strong>21h00</strong> (heure de Paris).' : 'Aug 27, <strong>3pm ET / 9pm CEST</strong>.' ?></li>
            <li><strong>▶️ <?= $fr ? 'YouTube + site GTA VI (GRATUIT)' : 'YouTube + GTA VI site (FREE)' ?></strong> — <?= $fr ? '28 août, <strong>3h00 du matin</strong> (heure de Paris), après la fenêtre exclusive Netflix (~6 h).' : 'Aug 27, <strong>9pm ET</strong> (~6h after the Netflix window).' ?></li>
        </ul>
    </div>
    <p class="muted" style="font-size:.9rem"><?= $fr ? 'Horaires internationaux (avant-première) :' : 'International times (premiere):' ?> 12 PM PT · 3 PM ET · 8 PM BST · 9 PM CEST.</p>

    <h2>🎮 <?= $fr ? 'À quoi s\'attendre' : 'What to expect' ?></h2>
    <ul>
        <li><?= $fr ? 'Du <strong>gameplay commenté</strong> (dans l\'esprit des trailers de gameplay de Red Dead Redemption 2).' : '<strong>Narrated gameplay</strong> (in the spirit of the Red Dead Redemption 2 gameplay trailers).' ?></li>
        <li><?= $fr ? 'Vice City & l\'État de Leonida, le duo Jason Duval & Lucia Caminos.' : 'Vice City & the state of Leonida, the Jason Duval & Lucia Caminos duo.' ?></li>
        <li><?= $fr ? 'Durée <strong>~20 min selon des fuites</strong> (non confirmé par Rockstar).' : 'Runtime <strong>~20 min per leaks</strong> (not confirmed by Rockstar).' ?></li>
        <li><?= $fr ? '<strong>Pas de report</strong> attendu : la sortie reste au 19 novembre 2026 (PS5, Xbox Series X|S).' : '<strong>No delay</strong> expected: release stays November 19, 2026 (PS5, Xbox Series X|S).' ?></li>
    </ul>

    <h2>❓ <?= $fr ? 'Questions fréquentes' : 'FAQ' ?></h2>
    <?php foreach ($faq as $qa): ?>
        <div class="lore-block glass" style="margin-top:1rem">
            <h3 style="margin:0 0 .4rem"><?= e($qa[0]) ?></h3>
            <p class="muted" style="margin:0"><?= e($qa[1]) ?></p>
        </div>
    <?php endforeach; ?>

    <p style="margin-top:2rem">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/news.php'))) ?>"><?= $fr ? 'Toutes les actus GTA 6' : 'All GTA 6 news' ?></a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/gta6.php'))) ?>"><?= $fr ? 'Le dossier complet' : 'The full hub' ?></a>
    </p>
    <p class="muted" style="margin-top:1rem;font-size:.85rem"><?= $fr
        ? 'ViceHub X est un média indépendant non officiel. GTA, Grand Theft Auto et Vice City sont des marques de Rockstar Games / Take-Two. La durée exacte reste à confirmer officiellement.'
        : 'ViceHub X is an independent, unofficial media. GTA, Grand Theft Auto and Vice City are trademarks of Rockstar Games / Take-Two. The exact runtime is yet to be officially confirmed.' ?></p>
</section>
<script>
(function(){
  var box=document.getElementById('vhx-cd'); if(!box)return;
  var nf=new Date(box.dataset.netflix).getTime(), yt=new Date(box.dataset.youtube).getTime();
  var lbl=box.querySelector('[data-cd-label]'), tmr=box.querySelector('[data-cd-timer]'), sub=box.querySelector('[data-cd-sub]');
  var FR=<?= $fr ? 'true' : 'false' ?>;
  function pad(n){return (n<10?'0':'')+n;}
  function tick(){
    var now=Date.now(), t;
    if(now<nf){ lbl.textContent=FR?'Avant-première Netflix dans':'Netflix premiere in'; t=nf-now; sub.textContent=FR?'Puis gratuit sur YouTube à 3h (heure de Paris).':'Then free on YouTube ~6h later.'; }
    else if(now<yt){ lbl.textContent=FR?'🔴 En ce moment sur Netflix — YouTube gratuit dans':'🔴 Live on Netflix — free on YouTube in'; t=yt-now; sub.textContent=FR?'Abonnement Netflix requis pour le voir maintenant.':'Netflix subscription needed to watch now.'; }
    else { lbl.textContent=FR?'✅ Disponible gratuitement':'✅ Now available for free'; tmr.textContent=FR?'Sur YouTube & le site GTA VI':'On YouTube & the GTA VI site'; sub.textContent=''; return; }
    var d=Math.floor(t/864e5), h=Math.floor(t%864e5/36e5), m=Math.floor(t%36e5/6e4), s=Math.floor(t%6e4/1e3);
    tmr.textContent=(d>0?d+(FR?'j ':'d '):'')+pad(h)+':'+pad(m)+':'+pad(s);
  }
  tick(); setInterval(tick,1000);
})();
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
