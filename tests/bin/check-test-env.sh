#!/usr/bin/env bash
# check-test-env.sh
#
# Pre-flight health check for the PHPUnit test environment.
#
# Probes the components needed for `composer test` and, on failure,
# dispatches a granular repair to install-wc-tests.sh by setting REPAIR_*
# env vars so only the broken components get rebuilt.
#
# The probes are intentionally cheap. The most important one is WP_CONFIG —
# it catches the cross-plugin /tmp drift where another woocommerce extension
# (e.g. USPS) overwrote /tmp/wordpress-tests-lib/wp-tests-config.php with a
# different DB_HOST port.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/tests/docker-compose.yml"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"

failed=0
results=()
declare -A REPAIR=()

probe() {
    local label="$1" repair_key="$2"
    shift 2
    if "$@" > /dev/null 2>&1; then
        results+=("   ✅ $label")
    else
        results+=("   ❌ $label")
        failed=1
        [[ -n "$repair_key" ]] && REPAIR[$repair_key]=1
    fi
}

probe_wp_config_db_host() {
    local cfg="${WP_TESTS_DIR}/wp-tests-config.php"
    [[ -f "$cfg" ]] || return 1
    grep -qE "define\([[:space:]]*'DB_HOST'[[:space:]]*,[[:space:]]*'${TEST_DB_HOST}:${TEST_DB_PORT}'[[:space:]]*\)" "$cfg"
}

probe "PHPUnit binary" \
    VENDOR test -x "$PROJECT_ROOT/vendor/bin/phpunit"

probe "WC test framework" \
    WC test -f "${WC_DEVELOP_DIR}/tests/legacy/bootstrap.php"

probe "WC composer dependencies" \
    WC test -f "${WC_DEVELOP_DIR}/vendor/autoload.php"

probe "WC feature config generated" \
    WC test -f "${WC_DEVELOP_DIR}/includes/react-admin/feature-config.php"

probe "WP core" \
    WP test -f "${WP_CORE_DIR}/wp-includes/version.php"

probe "WP test library" \
    WP test -f "${WP_TESTS_DIR}/includes/functions.php"

probe "WP tests config exists" \
    WP test -f "${WP_TESTS_DIR}/wp-tests-config.php"

probe "WP tests config DB_HOST matches ${TEST_DB_HOST}:${TEST_DB_PORT}" \
    WP_CONFIG probe_wp_config_db_host

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
    echo "   Components to repair:" \
        "${REPAIR[VENDOR]:+VENDOR}" \
        "${REPAIR[WC]:+WC}" \
        "${REPAIR[WP]:+WP}" \
        "${REPAIR[WP_CONFIG]:+WP_CONFIG}" \
        "${REPAIR[DB]:+DB}" \
        "${REPAIR[DB_PORT]:+DB_PORT}"
    echo "   ℹ️  To force a full reinstall instead, run:  bash tests/bin/install-wc-tests.sh --force"
    echo
    exec env \
        REPAIR_DISPATCHED=1 \
        REPAIR_VENDOR="${REPAIR[VENDOR]-0}" \
        REPAIR_WC="${REPAIR[WC]-0}" \
        REPAIR_WP="${REPAIR[WP]-0}" \
        REPAIR_WP_CONFIG="${REPAIR[WP_CONFIG]-0}" \
        REPAIR_DB="${REPAIR[DB]-0}" \
        REPAIR_DB_PORT="${REPAIR[DB_PORT]-0}" \
        bash "$SCRIPT_DIR/install-wc-tests.sh"
fi

# Healthy path. Stay quiet when chained from run-tests.sh; print a confirmation
# only when invoked directly.
if [[ -n "${REENTRY_FROM_SETUP:-}" ]]; then
    echo "✅ Test environment is ready. Nothing to repair."
    echo "   To force a full reinstall anyway, run:  bash tests/bin/install-wc-tests.sh --force"
fi
