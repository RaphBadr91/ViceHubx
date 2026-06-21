#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — Provisionnement du "Web Scraper Agent" (plan de contrôle)
#  Crée l'environnement + l'agent Anthropic Managed Agents via la CLI `ant`.
#
#  Prérequis :
#    export ANTHROPIC_API_KEY="sk-ant-..."
#  Puis :
#    bash agents/setup.sh
# ==================================================================
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
IDS_FILE="$HERE/.agent-ids"

# --- 1. Installer la CLI `ant` si absente -------------------------
if ! command -v ant >/dev/null 2>&1; then
  echo "→ Installation de la CLI Anthropic (ant)…"
  case "$(uname -s)" in
    Darwin)
      brew install anthropics/tap/ant
      ;;
    Linux)
      VERSION="${ANT_VERSION:-latest}"
      echo "  Sous Linux, récupérez le binaire depuis :"
      echo "  https://github.com/anthropics/anthropic-cli/releases"
      echo "  ou : go install github.com/anthropics/anthropic-cli/cmd/ant@latest"
      ;;
    *)
      echo "  Plateforme non gérée automatiquement — installez `ant` manuellement." ;;
  esac
fi

command -v ant >/dev/null 2>&1 || { echo "✗ CLI `ant` introuvable. Installez-la puis relancez."; exit 1; }
: "${ANTHROPIC_API_KEY:?Définissez ANTHROPIC_API_KEY avant de lancer ce script.}"

# --- 2. Créer l'environnement ------------------------------------
echo "→ Création de l'environnement…"
ENV_ID="$(ant beta:environments create < "$HERE/web-scraper.environment.yaml" --transform id -r)"
echo "  environment_id = $ENV_ID"

# --- 3. Créer l'agent --------------------------------------------
echo "→ Création de l'agent…"
AGENT_ID="$(ant beta:agents create < "$HERE/web-scraper-agent.agent.yaml" --transform id -r)"
echo "  agent_id = $AGENT_ID"

# --- 4. Sauvegarder les IDs (ignorés par git) --------------------
cat > "$IDS_FILE" <<EOF
AGENT_ID=$AGENT_ID
ENV_ID=$ENV_ID
EOF

echo
echo "✓ Terminé. IDs enregistrés dans agents/.agent-ids"
echo "  Lancez une session avec :  node agents/run-session.mjs \"<votre requête de scraping>\""
echo
echo "  Mise à jour ultérieure de l'agent (nouvelle version) :"
echo "    ant beta:agents update --agent-id $AGENT_ID --version <N> < agents/web-scraper-agent.agent.yaml"
