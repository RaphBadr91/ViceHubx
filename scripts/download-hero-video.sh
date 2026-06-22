#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — Récupère la vidéo de fond du hero (générée par IA)
#  et son image-poster, puis les place dans public/assets/.
#  La vidéo s'active alors AUTOMATIQUEMENT sur la page d'accueil.
#
#  Usage :  bash scripts/download-hero-video.sh
# ==================================================================
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

VIDEO_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_092218_bd3eae4f-84c2-4357-bc9a-503c9d444866.mp4"
POSTER_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_091459_e851b3ac-912c-4cfe-a04e-264f17f2fc5c.png"

mkdir -p public/assets/video public/assets/img

echo "→ Vidéo de fond…"
curl -fSL "$VIDEO_URL" -o public/assets/video/hero.mp4
echo "→ Image poster…"
curl -fSL "$POSTER_URL" -o public/assets/img/hero-poster.png

echo
echo "✓ Terminé."
echo "  public/assets/video/hero.mp4      ($(du -h public/assets/video/hero.mp4 | cut -f1))"
echo "  public/assets/img/hero-poster.png ($(du -h public/assets/img/hero-poster.png | cut -f1))"
echo
echo "La vidéo est maintenant active sur la home. Recharge avec Cmd+Shift+R."
echo "Pense à committer ces fichiers (git add public/assets && git commit)."
