#!/usr/bin/env bash
# ==================================================================
#  ViceHub X — démarrage robuste du serveur PHP local.
#
#  Neutralise un éventuel auto_prepend_file / auto_append_file cassé
#  dans le php.ini du système (cas fréquent sur macOS Homebrew, où une
#  valeur "#" provoque :  Fatal error: Failed opening required '#').
#
#  Usage :
#    ./serve.sh           # http://localhost:8000
#    ./serve.sh 8080      # port personnalisé
# ==================================================================
cd "$(dirname "$0")" || exit 1
PORT="${1:-8000}"
echo "→ ViceHub X sur http://localhost:${PORT}  (Ctrl+C pour arrêter)"
exec php \
  -d auto_prepend_file= \
  -d auto_append_file= \
  -S "localhost:${PORT}"
