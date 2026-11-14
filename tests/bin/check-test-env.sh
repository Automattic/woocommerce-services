#!/usr/bin/env bash
# check-test-env.sh
#
# Pre-flight health check for the PHPUnit test environment.
#
# Probes the components needed for `composer run test` and, on failure,
# dispatches a granular repair to install-wc-tests.sh by setting REPAIR_*
# env vars so only the broken components get rebuilt.
#
# The probes are intentionally cheap and read-only. The shared WP/WC trees are
# probed without a lock; install-wc-tests.sh probes them again under the lock
# (another run may be setting them up right now) before it repairs anything.
# There is no DB_HOST probe: run-tests.sh writes this plugin's own
# wp-tests-config.php on every run, so other plugins cannot change it.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/tests/docker-compose.yml"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"

failed=0
results=()

# One plain variable per repairable component rather than an associative
# array: `declare -A` needs bash 4, and macOS still ships bash 3.2 as /bin/bash.
NEED_VENDOR=0
NEED_WC=0
NEED_WP=0
NEED_DB=0
NEED_DB_PORT=0

probe() {
    local label="$1" repair_key="$2"
    shift 2
    if "$@" > /dev/null 2>&1; then
        results+=("   ✅ $label")
    else
        results+=("   ❌ $label")
        failed=1
        [[ -n "$repair_key" ]] && printf -v "NEED_${repair_key}" '%s' 1
    fi
}

probe "PHPUnit binary" \
    VENDOR test -x "$PROJECT_ROOT/vendor/bin/phpunit"

probe "WC test framework" \
    WC test -f "${WC_DEVELOP_DIR}/tests/legacy/bootstrap.php"

probe "WC composer dependencies" \
    WC test -f "${WC_DEVELOP_DIR}/vendor/autoload.php"

# A WC tree whose composer install stopped early can have vendor/autoload.php
# but not the PHPUnit polyfills that WC's test bootstrap loads.
probe "WC PHPUnit polyfills" \
    WC test -f "${WC_DEVELOP_DIR}/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php"

probe "WC feature config generated" \
    WC test -f "${WC_DEVELOP_DIR}/includes/react-admin/feature-config.php"

probe "WP core" \
    WP test -f "${WP_CORE_DIR}/wp-includes/version.php"

probe "WP test library" \
    WP test -f "${WP_TESTS_DIR}/includes/functions.php"

probe "WP tests config template" \
    WP bash -c "test -f '${WP_TESTS_DIR}/wp-tests-config-sample.php' || test -f '${WP_TESTS_DIR}/wp-tests-config.php'"

probe "DB container running" \
    DB bash -c "docker compose -f '$COMPOSE_FILE' ps test_db --status running -q 2>/dev/null | grep -q ."

probe "DB accepting connections at ${TEST_DB_HOST}:${TEST_DB_PORT}" \
    DB_PORT bash -c "(echo > /dev/tcp/${TEST_DB_HOST}/${TEST_DB_PORT}) 2>/dev/null"

if [ "$failed" = "1" ]; then
    echo
    echo "❌ PHPUnit test environment is not ready:"
    echo
    for line in "${results[@]}"; do
        echo "$line"
    done
    echo
    echo "▶️  Running install-wc-tests.sh (granular repair)..."
    components=""
    for key in VENDOR WC WP DB DB_PORT; do
        flag="NEED_${key}"
        [[ "${!flag}" == "1" ]] && components="${components} ${key}"
    done
    echo "   Components to repair:${components}"
    echo "   ℹ️  To force a full reinstall instead, run:  bash tests/bin/install-wc-tests.sh --force"
    echo
    exec env \
        REPAIR_DISPATCHED=1 \
        REPAIR_VENDOR="$NEED_VENDOR" \
        REPAIR_WC="$NEED_WC" \
        REPAIR_WP="$NEED_WP" \
        REPAIR_DB="$NEED_DB" \
        REPAIR_DB_PORT="$NEED_DB_PORT" \
        bash "$SCRIPT_DIR/install-wc-tests.sh"
fi

# Healthy path. Stay quiet when chained from run-tests.sh; print a confirmation
# only when invoked directly.
if [[ -n "${REENTRY_FROM_SETUP:-}" ]]; then
    echo "✅ Test environment is ready. Nothing to repair."
    echo "   To force a full reinstall anyway, run:  bash tests/bin/install-wc-tests.sh --force"
fi
