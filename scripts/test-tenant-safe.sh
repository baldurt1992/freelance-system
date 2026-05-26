#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
API_DIR="$ROOT/api"
TIMEOUT_SECONDS="${TEST_TIMEOUT_SECONDS:-60}"
PHP_MEMORY_LIMIT="${TEST_PHP_MEMORY_LIMIT:-512M}"

if [[ $# -eq 0 ]]; then
  echo "Usage: bash scripts/test-tenant-safe.sh <phpunit-arg> [arg...]"
  echo "Example: bash scripts/test-tenant-safe.sh tests/Feature/Tenant/TemplateTaxTest.php --filter=default_template_fallback"
  exit 1
fi

cd "$API_DIR"

echo "[test-tenant-safe] timeout=${TIMEOUT_SECONDS}s memory_limit=${PHP_MEMORY_LIMIT}"

set +e
XDEBUG_MODE=off timeout --preserve-status "$TIMEOUT_SECONDS" \
  php -d memory_limit="$PHP_MEMORY_LIMIT" vendor/bin/phpunit "$@"
status=$?
set -e

if [[ $status -eq 124 ]]; then
  echo "[test-tenant-safe] timeout after ${TIMEOUT_SECONDS}s"
fi

exit "$status"
