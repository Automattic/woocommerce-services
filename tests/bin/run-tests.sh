#!/usr/bin/env bash
# run-tests.sh
#
# Orchestrates the full test run:
#   1. Health-check the test environment, repairing only the components that
#      fail their probe (cheapest = a single sed on wp-tests-config.php when
#      another plugin's setup wrote a different DB port into shared /tmp).
#   2. Run PHPUnit.
#
# Usage:
#   bash tests/bin/run-tests.sh
#   composer test
#   composer test -- --filter=test_taxjar_address_is_validated

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"

# ---------------------------------------------------------------------------
# 1. Health-check + granular repair
# ---------------------------------------------------------------------------
bash "$SCRIPT_DIR/check-test-env.sh"

# ---------------------------------------------------------------------------
# 2. Run PHPUnit (pass any extra args through, e.g. --filter)
# ---------------------------------------------------------------------------
echo "==> Running PHPUnit..."
# WC's bootstrap emits PHP notices/warnings to stderr that bury PHPUnit output.
# Filter only those known-noisy lines; pass everything else through so real
# PHPUnit errors (fatal errors, segfaults) remain visible.
"$PROJECT_ROOT/vendor/bin/phpunit" --testdox "$@" 2>&1 \
    | grep -Ev "^(PHP Deprecated|PHP Notice|PHP Warning|Xdebug)" \
    || true
