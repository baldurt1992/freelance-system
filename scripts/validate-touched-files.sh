#!/usr/bin/env bash
# Minimum validation gate for touched files (phase 0: PHP lint only when api/ exists).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ $# -eq 0 ]]; then
  echo "Usage: bash scripts/validate-touched-files.sh <file> [file...]"
  exit 1
fi

PHP_FILES=()
FRONTEND_FILES=()

for f in "$@"; do
  [[ -f "$f" ]] || { echo "Missing file: $f"; exit 1; }
  case "$f" in
    *.php) PHP_FILES+=("$f") ;;
    apps/web/*|apps/web/**/*|nuxt/*|nuxt/**/*)
      FRONTEND_FILES+=("$f")
      ;;
  esac
done

if [[ ${#PHP_FILES[@]} -gt 0 ]]; then
  echo "[validate] php -l (${#PHP_FILES[@]} file(s))"
  for f in "${PHP_FILES[@]}"; do
    php -l "$f"
  done
fi

if [[ ${#FRONTEND_FILES[@]} -gt 0 ]]; then
  if [[ -d "$ROOT/apps/web" ]] && [[ -f "$ROOT/apps/web/package.json" ]]; then
    echo "[validate] nuxi typecheck (apps/web)"
    (cd "$ROOT/apps/web" && pnpm exec nuxi typecheck)
  else
    echo "[validate] skip nuxi typecheck — apps/web not scaffolded yet"
  fi
fi

echo "[validate] done"
