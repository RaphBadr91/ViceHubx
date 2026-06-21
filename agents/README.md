# Web Scraper Agent — Anthropic Managed Agent

Agent **Anthropic Managed Agents** qui extrait des données structurées de pages
dynamiques / SPA via **Browser Use Cloud**.

| | |
|---|---|
| **Nom** | Web Scraper Agent |
| **Modèle** | `claude-sonnet-4-6` |
| **Boîte à outils** | `agent_toolset_20260401` + 2 outils custom (`browser_use_extract`, `submit_extraction`) |

L'approche suit la recommandation Anthropic : **plan de contrôle** (agent +
environnement) en **YAML versionné appliqué par la CLI `ant`**, **plan de
données** (sessions) piloté par le **SDK** (`agents/run-session.mjs`).

## Fichiers

| Fichier | Rôle |
|---|---|
| `web-scraper-agent.agent.yaml` | Définition de l'agent (modèle, system prompt, outils) |
| `web-scraper.environment.yaml` | Environnement cloud (egress non restreint) |
| `setup.sh` | Crée l'environnement + l'agent, enregistre les IDs |
| `run-session.mjs` | Orchestrateur runtime : session, flux d'événements, outils custom |

## Installation

```bash
# 1. CLI Anthropic
brew install anthropics/tap/ant            # macOS
# Linux : https://github.com/anthropics/anthropic-cli/releases

# 2. Clé API
export ANTHROPIC_API_KEY="sk-ant-..."

# 3. Créer l'agent + l'environnement (une seule fois)
bash agents/setup.sh                        # → agents/.agent-ids

# 4. (Runtime) dépendances + clé Browser Use (optionnelle)
npm i @anthropic-ai/sdk
export BROWSER_USE_API_KEY="..."            # sinon fallback fetch (pages statiques)

# 5. Lancer une extraction
node agents/run-session.mjs "Scrape les 5 derniers articles de https://exemple.com/news"
```

> ⚠️ **Sécurité** : la clé Browser Use n'entre **jamais** dans le conteneur de
> l'agent. L'outil `browser_use_extract` est résolu côté hôte par
> `run-session.mjs`, qui répond via `user.custom_tool_result` (cf. pattern
> « secrets host-side » des Managed Agents).

## Commande CLI équivalente (référence)

```bash
ant beta:environments create < agents/web-scraper.environment.yaml
ant beta:agents create        < agents/web-scraper-agent.agent.yaml
```

Mise à jour de l'agent (nouvelle version, verrou optimiste) :

```bash
ant beta:agents update --agent-id "$AGENT_ID" --version <N> < agents/web-scraper-agent.agent.yaml
```
