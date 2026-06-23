#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — Télécharge les wallpapers HD (fichiers PROPRES) dans
#  /storage/wallpapers (dossier privé). L'aperçu filigrané « ViceHub X »
#  est généré automatiquement par preview.php ; le fichier propre n'est
#  livré qu'après paiement (download.php).
#
#  Usage :  bash scripts/make-wallpapers.sh
# ==================================================================
cd "$(dirname "$0")/.." || exit 1
CDN="https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E"

# nom_local|url  (le nom doit correspondre à digital_file en base)
WALLS=(
  "wall-skyline.png|$CDN/hf_20260623_172315_397149d2-53ae-4220-a743-24d7d3a84772.png"
  "wall-beach.png|$CDN/hf_20260623_172340_91a21628-212b-4981-839b-87bc2342942c.png"
  "wall-supercar.png|$CDN/hf_20260623_172342_3e4d7750-f193-4071-9e34-49f99fbe1f8f.png"
  "wall-aerial.png|$CDN/hf_20260623_172345_bc983d30-35a6-4e84-a0dc-614d8b9c76b2.png"
  "wall-synthwave.png|$CDN/hf_20260623_172348_9c7d9133-abf1-4bfa-8b48-f85f1b8102ef.png"
  "wall-nightlife.png|$CDN/hf_20260623_172351_d3bd540a-1165-4f07-84e8-d6a15a9f016e.png"
  "wall-marina.png|$CDN/hf_20260623_172353_b5db090c-aff9-45fb-8daa-70865c61e5fd.png"
  "wall-flamingo.png|$CDN/hf_20260623_172356_17c8f58c-9bd4-4442-ae37-a76cfa377f9f.png"
)

mkdir -p storage/wallpapers public/assets/img/shop/cache
echo "→ Téléchargement des ${#WALLS[@]} wallpapers HD (fichiers propres)…"
for entry in "${WALLS[@]}"; do
  name="${entry%%|*}"; url="${entry#*|}"
  curl -fSL "$url" -o "storage/wallpapers/$name" && echo "   ✓ $name" || echo "   ⚠ $name (ignoré)"
done

# On purge le cache d'aperçus pour qu'il soit régénéré avec les nouvelles images
rm -f public/assets/img/shop/cache/wall-*.jpg 2>/dev/null

echo
echo "✓ Wallpapers : storage/wallpapers/ (${#WALLS[@]} fichiers propres, privés)"
echo "  Les aperçus filigranés sont générés automatiquement par preview.php."
echo "  Commit :  git add storage/wallpapers && git commit -m 'wallpapers HD'"
