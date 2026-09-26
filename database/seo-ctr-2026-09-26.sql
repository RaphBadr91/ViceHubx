-- ViceHub X — Correctif CTR ciblé (Search Console 26 sept. 2026)
-- La page "storage requirements" cumule des impressions mais 0 clic (pos ~9).
-- Titre + meta plus directs (question + chiffres + année) pour déclencher le clic.
-- À coller dans phpMyAdmin (SQL). Idempotent. Effet immédiat.

UPDATE articles SET
  meta_title = 'GTA 6 Storage: How Much Space on PS5 & Xbox? (2026)',
  meta_description = 'How much storage does GTA 6 need on PS5 and Xbox Series X|S? The expected install size, free space to prepare and SSD tips — everything for launch day 2026.'
WHERE slug = 'gta-6-storage-requirements-space-needed-on-ps5-xbox-series-x-s';
