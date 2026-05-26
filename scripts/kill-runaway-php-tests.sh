#!/usr/bin/env bash
set -euo pipefail

pattern='php artisan test|vendor/bin/phpunit'

if ! pgrep -fa "$pattern" >/dev/null; then
  echo "[kill-runaway-php-tests] no matching php test processes"
  exit 0
fi

echo "[kill-runaway-php-tests] sending TERM"
pkill -TERM -f "$pattern" || true
sleep 2

if pgrep -fa "$pattern" >/dev/null; then
  echo "[kill-runaway-php-tests] sending KILL to remaining processes"
  pkill -KILL -f "$pattern" || true
fi

echo "[kill-runaway-php-tests] done"
