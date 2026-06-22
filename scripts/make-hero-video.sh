#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — Assemble la vidéo de fond du hero (~25 s, monde GTA6)
#  Télécharge 3 plans cinématiques (générés par IA) et les monte en
#  une seule vidéo via ffmpeg, puis la place dans public/assets/.
#  La vidéo s'active automatiquement sur la page d'accueil.
#
#  Prérequis : ffmpeg  (sur Mac :  brew install ffmpeg)
#  Usage     : bash scripts/make-hero-video.sh
# ==================================================================
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

# --- Plans générés (ordre du montage) ---
CLIP1_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_093447_8c39b466-cde1-4c43-8a37-43470c00da5e.mp4"   # Night drive — boulevard néon (9s)
CLIP2_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_093513_c9a90c6d-7340-4dfd-8963-410993e23456.mp4"   # Cruise plage — golden hour (8s)
CLIP3_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_093551_0ec3d71f-b1a6-4ca4-9284-873fd2e1f381.mp4"   # Survol aérien — métropole néon (8s)
POSTER_URL="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/hf_20260622_091459_e851b3ac-912c-4cfe-a04e-264f17f2fc5c.png"

if ! command -v ffmpeg >/dev/null 2>&1; then
  echo "✗ ffmpeg requis. Installez-le :  brew install ffmpeg"; exit 1
fi

mkdir -p public/assets/video public/assets/img
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT

echo "→ Téléchargement des 3 plans…"
curl -fSL "$CLIP1_URL" -o "$TMP/c1.mp4"
curl -fSL "$CLIP2_URL" -o "$TMP/c2.mp4"
curl -fSL "$CLIP3_URL" -o "$TMP/c3.mp4"
echo "→ Image poster…"
curl -fSL "$POSTER_URL" -o public/assets/img/hero-poster.png

echo "→ Montage (1280x720, 30 fps, fondus enchaînés)…"
# Fondus enchaînés (xfade) entre les plans pour un rendu premium.
ffmpeg -y -loglevel error \
  -i "$TMP/c1.mp4" -i "$TMP/c2.mp4" -i "$TMP/c3.mp4" \
  -filter_complex "\
[0:v]scale=1280:720,setsar=1,fps=30,format=yuv420p[v0];\
[1:v]scale=1280:720,setsar=1,fps=30,format=yuv420p[v1];\
[2:v]scale=1280:720,setsar=1,fps=30,format=yuv420p[v2];\
[v0][v1]xfade=transition=fade:duration=0.8:offset=8.2[x1];\
[x1][v2]xfade=transition=fade:duration=0.8:offset=15.4[outv]" \
  -map "[outv]" -an -c:v libx264 -crf 20 -preset medium -movflags +faststart \
  public/assets/video/hero.mp4 || {
    echo "⚠ Fondus indisponibles, montage simple…"
    ffmpeg -y -loglevel error \
      -i "$TMP/c1.mp4" -i "$TMP/c2.mp4" -i "$TMP/c3.mp4" \
      -filter_complex "\
[0:v]scale=1280:720,setsar=1,fps=30[a];\
[1:v]scale=1280:720,setsar=1,fps=30[b];\
[2:v]scale=1280:720,setsar=1,fps=30[c];\
[a][b][c]concat=n=3:v=1:a=0[outv]" \
      -map "[outv]" -an -c:v libx264 -crf 20 -pix_fmt yuv420p -movflags +faststart \
      public/assets/video/hero.mp4
  }

echo
echo "✓ Vidéo prête : public/assets/video/hero.mp4 ($(du -h public/assets/video/hero.mp4 | cut -f1))"
echo "  Elle s'active automatiquement sur la home. Recharge avec Cmd+Shift+R."
echo "  Pense à committer : git add public/assets && git commit -m 'hero video'"
