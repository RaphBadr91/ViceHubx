-- ============================================================================
--  ViceHub X — Article NEWS : « GTA 6 gameplay le 27 août (Netflix puis YouTube) »
--  Fait apparaître l'événement dans la section « Breaking News » de l'accueil
--  et dans /news (en plus de la page SEO /gta6-extended-look).
--
--  À LANCER UNE FOIS dans phpMyAdmin (cPanel → phpMyAdmin → base vicehubx → SQL).
--  Idempotent : ne crée pas de doublon si relancé. Faits vérifiés ; durée ~20 min
--  présentée comme fuite NON confirmée par Rockstar.
-- ============================================================================

ALTER TABLE articles ADD COLUMN IF NOT EXISTS meta_title       VARCHAR(90)  DEFAULT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS meta_description VARCHAR(200) DEFAULT NULL;

-- ---------- Version FR ----------
INSERT INTO articles (category_id, lang, title, slug, excerpt, meta_title, meta_description, body, image, status, published_at)
SELECT
  (SELECT id FROM categories WHERE slug='news' LIMIT 1),
  'fr',
  'GTA 6 : le gameplay arrive le 27 août sur Netflix, puis sur YouTube',
  'gta-6-gameplay-netflix-27-aout',
  'Rockstar diffuse du gameplay de GTA 6 le 27 août : d''abord sur Netflix à 21h, puis gratuitement sur YouTube vers 3h du matin. Tous les horaires et ce qu''il faut savoir.',
  'GTA 6 gameplay le 27 août : Netflix puis YouTube (horaires)',
  'GTA 6 : la présentation de gameplay « An Extended Look » sort le 27 août 2026, d''abord sur Netflix (21h) puis gratuitement sur YouTube (3h). Horaires et infos.',
  CONCAT(
    '<p><strong>Rockstar diffuse enfin du gameplay de GTA 6.</strong> La présentation « Grand Theft Auto VI: An Extended Look » est programmée pour le <strong>27 août 2026</strong> : d''abord en exclusivité sur <strong>Netflix à 21h00</strong> (heure de Paris), puis <strong>gratuitement</strong> sur la chaîne YouTube de Rockstar et le site officiel GTA VI vers <strong>3h00 du matin</strong> (nuit du 27 au 28).</p>',
    '<h2>Quand et où regarder</h2>',
    '<ul>',
    '<li><strong>Netflix (avant-première)</strong> : 27 août, 21h00 (Paris) — un abonnement Netflix est requis pour la voir en premier.</li>',
    '<li><strong>YouTube + site GTA VI (gratuit)</strong> : environ 6 heures plus tard, soit vers 3h00 du matin (heure de Paris).</li>',
    '</ul>',
    '<p>Horaires internationaux de l''avant-première : 12h00 PT · 15h00 ET · 20h00 (Royaume-Uni) · 21h00 (Europe, CEST).</p>',
    '<h2>À quoi s''attendre</h2>',
    '<p>Il s''agit d''une présentation de <strong>gameplay commenté</strong>, dans l''esprit des trailers de gameplay de Red Dead Redemption 2. On devrait y voir Vice City et l''État de Leonida en action, ainsi que le duo de protagonistes Jason Duval et Lucia Caminos.</p>',
    '<p>La <strong>durée exacte n''est pas confirmée</strong> par Rockstar. Selon des fuites (via le support client de Netflix), la vidéo durerait environ <strong>20 minutes</strong> — bien plus que les trailers habituels (4 à 6 minutes). À prendre avec prudence tant que Rockstar ne l''officialise pas.</p>',
    '<h2>GTA 6 est-il repoussé ?</h2>',
    '<p>Non. La sortie reste fixée au <strong>19 novembre 2026</strong> sur PS5 et Xbox Series X|S. Cet événement est une présentation de gameplay, pas une annonce de report.</p>',
    '<p>Retrouve le compte à rebours et notre guide complet « comment regarder » sur notre <a href="/gta6-extended-look">page dédiée à l''avant-première GTA 6</a>.</p>',
    '<h2>FAQ</h2>',
    '<h3>Comment voir le gameplay de GTA 6 gratuitement ?</h3>',
    '<p>Sans abonnement Netflix, attends la sortie gratuite sur la chaîne YouTube de Rockstar Games et le site GTA VI, environ 6 heures après la première Netflix, soit vers 3h du matin (heure de Paris).</p>',
    '<h3>Faut-il un abonnement Netflix ?</h3>',
    '<p>Uniquement pour la voir en avant-première à 21h. Après la fenêtre d''exclusivité (~6h), la vidéo est gratuite pour tout le monde sur YouTube et le site officiel.</p>',
    '<h3>Combien de temps dure la vidéo ?</h3>',
    '<p>La durée n''est pas confirmée officiellement. Des fuites évoquent environ 20 minutes.</p>'
  ),
  '/public/assets/img/scenes/nightlife.png',
  'published',
  NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE slug='gta-6-gameplay-netflix-27-aout');

SET @fr := (SELECT id FROM articles WHERE slug='gta-6-gameplay-netflix-27-aout' LIMIT 1);

-- ---------- Version EN (paire hreflang via source_id) ----------
INSERT INTO articles (category_id, lang, source_id, title, slug, excerpt, meta_title, meta_description, body, image, status, published_at)
SELECT
  (SELECT id FROM categories WHERE slug='news' LIMIT 1),
  'en',
  @fr,
  'GTA 6 Gameplay Drops August 27 on Netflix, Then Free on YouTube',
  'gta-6-gameplay-netflix-august-27',
  'Rockstar airs GTA 6 gameplay on August 27: first on Netflix, then free on YouTube ~6 hours later. All the times and what to expect.',
  'GTA 6 Gameplay Aug 27: Netflix Then Free on YouTube',
  'GTA 6''s "An Extended Look" gameplay airs August 27, 2026 — first on Netflix (3pm ET), then free on YouTube. Times, runtime and what to expect.',
  CONCAT(
    '<p><strong>Rockstar is finally showing GTA 6 gameplay.</strong> "Grand Theft Auto VI: An Extended Look" is set for <strong>August 27, 2026</strong>: first exclusively on <strong>Netflix at 3pm ET / 9pm CEST</strong>, then <strong>free</strong> on Rockstar''s YouTube channel and the official GTA VI site at <strong>9pm ET</strong> (about 6 hours later).</p>',
    '<h2>When and where to watch</h2>',
    '<ul>',
    '<li><strong>Netflix (early premiere)</strong>: Aug 27, 3pm ET / 9pm CEST — a Netflix subscription is required to watch it first.</li>',
    '<li><strong>YouTube + GTA VI site (free)</strong>: about 6 hours later (9pm ET).</li>',
    '</ul>',
    '<p>International premiere times: 12pm PT · 3pm ET · 8pm BST · 9pm CEST.</p>',
    '<h2>What to expect</h2>',
    '<p>This is a <strong>narrated gameplay</strong> showcase, in the spirit of the Red Dead Redemption 2 gameplay trailers. Expect Vice City and the state of Leonida in action, plus the protagonist duo Jason Duval and Lucia Caminos.</p>',
    '<p>The <strong>exact runtime is not confirmed</strong> by Rockstar. Leaks (via a Netflix support agent) suggest around <strong>20 minutes</strong> — far longer than typical trailers (4-6 minutes). Treat it with caution until Rockstar confirms.</p>',
    '<h2>Is GTA 6 delayed?</h2>',
    '<p>No. The release stays set for <strong>November 19, 2026</strong> on PS5 and Xbox Series X|S. This event is a gameplay showcase, not a delay announcement.</p>',
    '<p>See the live countdown and our full "how to watch" guide on our <a href="/gta6-extended-look?lang=en">dedicated GTA 6 reveal page</a>.</p>',
    '<h2>FAQ</h2>',
    '<h3>How can I watch GTA 6 gameplay for free?</h3>',
    '<p>Without Netflix, wait for the free release on Rockstar Games'' YouTube channel and the GTA VI site, roughly 6 hours after the Netflix premiere (9pm ET).</p>',
    '<h3>Do I need Netflix?</h3>',
    '<p>Only to watch the early premiere at 3pm ET. After the ~6-hour exclusive window, the video is free for everyone on YouTube and the official site.</p>',
    '<h3>How long is the video?</h3>',
    '<p>The runtime is not officially confirmed. Leaks suggest around 20 minutes.</p>'
  ),
  '/public/assets/img/scenes/nightlife.png',
  'published',
  NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE slug='gta-6-gameplay-netflix-august-27');

-- Vérification : SELECT id, lang, slug, status, published_at FROM articles WHERE slug LIKE 'gta-6-gameplay-netflix%';
