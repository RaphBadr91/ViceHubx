#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — Vidéo de fond du hero (~30s, monde GTA6) + visuels de
#  la galerie "Univers". Télécharge les plans cinématiques générés
#  par IA, les monte avec ffmpeg, et récupère les images de scènes.
#  Tout s'active AUTOMATIQUEMENT sur la page d'accueil.
#
#  Prérequis : ffmpeg  (sur Mac :  brew install ffmpeg)
#  Usage     : bash scripts/make-hero-video.sh
# ==================================================================
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
CDN="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E"

# --- Plans du montage (ordre = rythme) ---
CLIPS=(
  "$CDN/hf_20260622_163349_83ec4749-81dc-406e-a59c-bd961d63db73.mp4"  # Boulevard de nuit : trafic dense + passants (5s)
  "$CDN/hf_20260622_131132_beb29382-3ff9-4429-933e-f3fdd03a9f55.mp4"  # Police-poursuite (8s)
  "$CDN/hf_20260622_131652_09f2e6b7-a3b4-482a-bc81-da676ae5e614.mp4"  # Hélicoptère de nuit (8s)
  "$CDN/hf_20260622_093513_c9a90c6d-7340-4dfd-8963-410993e23456.mp4"  # Cruise plage golden hour (8s)
  "$CDN/hf_20260622_093551_0ec3d71f-b1a6-4ca4-9284-873fd2e1f381.mp4"  # Survol aérien métropole (8s)
)
POSTER_URL="$CDN/hf_20260622_130727_e28cfd20-aeab-4f18-9e8f-cace0ad0de40.png"  # poster (police, dynamique)

# --- Visuels de la galerie "Univers" (nom_local|url) — 8 scènes ---
SCENES=(
  "night.png|$CDN/hf_20260622_091459_e851b3ac-912c-4cfe-a04e-264f17f2fc5c.png"
  "beach-cruise.png|$CDN/hf_20260622_093428_1b56209f-dc8e-4d0c-90f9-940c7eef4a14.png"
  "aerial.png|$CDN/hf_20260622_093430_c3918a3d-0345-4e6f-95ba-fbd3f76002bb.png"
  "police.png|$CDN/hf_20260622_130727_e28cfd20-aeab-4f18-9e8f-cace0ad0de40.png"
  "heli.png|$CDN/hf_20260622_130728_b0a5ded5-d988-4809-8a23-58ac81d030ae.png"
  "plane.png|$CDN/hf_20260622_130844_81ba4d71-ed7e-4fdd-b9e2-4536e5daa40e.png"
  "marina.png|$CDN/hf_20260622_130830_b4ff6360-6bf4-41a0-a68c-2414f21752a3.png"
  "beachlife.png|$CDN/hf_20260622_130831_13fde152-b662-485a-9258-94fc8cc5b2c5.png"
  "map.png|$CDN/hf_20260622_170731_cf090cac-cc1f-4236-b4d1-02e1a91b544e.png"
  "veh-supercar.png|$CDN/hf_20260622_221229_4a7dd5a9-3bd6-4c05-a01d-8f1731e6b58e.png"
  "veh-muscle.png|$CDN/hf_20260622_221231_d13edf33-58a7-4409-91d1-66526e8ab0ac.png"
  "veh-boat.png|$CDN/hf_20260622_221413_6620c79c-ce34-4e6e-bfb6-f85868a62be7.png"
  "veh-swamp.png|$CDN/hf_20260622_221415_dc91409f-d15d-4d6e-9d6d-739c52d3b0f3.png"
  "veh-vtol.png|$CDN/hf_20260622_221417_f592d488-c0b3-4de3-aba5-91762ab1495c.png"
)

if ! command -v ffmpeg >/dev/null 2>&1; then
  echo "✗ ffmpeg requis. Installez-le :  brew install ffmpeg"; exit 1
fi

mkdir -p public/assets/video public/assets/img public/assets/img/scenes
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT

echo "→ Téléchargement des ${#CLIPS[@]} plans…"
inputs=(); i=0
for url in "${CLIPS[@]}"; do
  i=$((i+1)); curl -fSL "$url" -o "$TMP/c$i.mp4"; inputs+=(-i "$TMP/c$i.mp4")
done

echo "→ Image poster…"
curl -fSL "$POSTER_URL" -o public/assets/img/hero-poster.png

echo "→ Visuels de la galerie Univers…"
for entry in "${SCENES[@]}"; do
  name="${entry%%|*}"; url="${entry#*|}"
  curl -fSL "$url" -o "public/assets/img/scenes/$name" && echo "   ✓ $name" || echo "   ⚠ $name (ignoré)"
done

echo "→ Montage (1280x720, 30 fps)…"
# Normalise chaque plan puis concatène (cuts nets, robuste).
fc=""
for n in $(seq 0 $(( ${#CLIPS[@]} - 1 ))); do
  fc+="[$n:v]scale=1280:720,setsar=1,fps=30,format=yuv420p[v$n];"
done
for n in $(seq 0 $(( ${#CLIPS[@]} - 1 ))); do fc+="[v$n]"; done
fc+="concat=n=${#CLIPS[@]}:v=1:a=0[outv]"

ffmpeg -y -loglevel error "${inputs[@]}" -filter_complex "$fc" \
  -map "[outv]" -an -c:v libx264 -crf 20 -preset medium -movflags +faststart \
  public/assets/video/hero.mp4

echo
echo "✓ Vidéo : public/assets/video/hero.mp4 ($(du -h public/assets/video/hero.mp4 | cut -f1))"
echo "✓ Galerie : public/assets/img/scenes/ (${#SCENES[@]} visuels)"
echo "  Tout s'active automatiquement. Recharge avec Cmd+Shift+R."
echo "  Commit :  git add public/assets && git commit -m 'hero video + scenes'"
