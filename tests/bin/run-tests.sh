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
# Output format. --testdox prints one readable line per test (~180 for the full
# suite) — pleasant for a human, but ~25x PHPUnit's compact progress output, so
# it floods pre-commit hooks, redirected logs and AI-agent context. Resolve it:
#   TESTDOX=1  → always --testdox
#   TESTDOX=0  → always compact
#   unset      → --testdox only when stdout is an interactive terminal
fmt=()
case "${TESTDOX:-auto}" in
    1) fmt=( --testdox ) ;;
    0) : ;;
    *) [ -t 1 ] && fmt=( --testdox ) ;;
esac
# Don't pass --testdox twice if the caller already supplied it.
for _a in "$@"; do [[ "$_a" == "--testdox" ]] && { fmt=(); break; }; done

# WC's bootstrap emits PHP notices/warnings to stderr that bury PHPUnit output.
# Filter only those known-noisy lines; pass everything else through so real
# PHPUnit errors (fatal errors, segfaults) remain visible.
#
# Exit-code propagation: without pipefail the pipeline's status is grep's (0
# whenever it prints a line), which masks a failed suite and makes `composer
# test` exit 0 on red — defeating the point of the command. We enable pipefail
# and capture PHPUnit's own status via PIPESTATUS[0], then exit with it so
# callers and pre-commit hooks see the true result. The status is read in the
# `|| ...` branch on purpose: a bare `|| true` would run `true`, which is itself
# a pipeline and would overwrite PIPESTATUS before we could read it.
set -o pipefail
status=0
# tee the filtered output to a log so we can additionally detect an empty run.
# mktemp needs an explicit XXXXXX template to work on BSD/macOS as well as GNU.
phpunit_log="$(mktemp "${TEST_TMP_DIR:-/tmp}/wcs-phpunit.XXXXXX")"
"$PROJECT_ROOT/vendor/bin/phpunit" "${fmt[@]}" "$@" 2>&1 \
    | grep -Ev "^(PHP Deprecated|PHP Notice|PHP Warning|Xdebug)" \
    | tee "$phpunit_log" \
    || status="${PIPESTATUS[0]}"
# PHPUnit exits 0 even when it runs zero tests (it prints "No tests executed!"),
# so a broken bootstrap or misconfigured suite would report green. Treat an
# empty run as a failure too (see GitHub #2638).
if [ "$status" -eq 0 ] && grep -qF 'No tests executed' "$phpunit_log"; then
    echo "PHPUnit executed zero tests — failing the run." >&2
    status=1
fi
rm -f "$phpunit_log"
exit "$status"
