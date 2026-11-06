#!/usr/bin/env bash
# run-tests.sh
#
# Orchestrates the full test run:
#   1. Health-check the test environment, repairing only the components that
#      fail their probe.
#   2. Register this run as a user of the shared WP/WC trees (shared-trees.sh),
#      so no other plugin's repair or --force replaces them while it runs.
#   3. Write this plugin's own wp-tests-config.php (DB_HOST = our container).
#   4. Run PHPUnit.
#
# Several plugins (or two checkouts of one plugin on different
# WC_TEST_COMPOSE_PROJECT / WC_TEST_DB_PORT) can run this at the same time.
# They only wait for each other while one of them installs or replaces a
# shared tree; the waiting run prints who it is waiting for.
#
# Usage:
#   bash tests/bin/run-tests.sh
#   composer test   (or the repo's own test script)
#   bash tests/bin/run-tests.sh --filter=<test name>
#
# Output format:
#   Compact by default (best for CI/agents). Set TESTDOX=1 for readable
#   per-test output, TESTDOX=0 to force compact. Unset auto-enables --testdox
#   only when stdout is a terminal (an interactive human), never in a pipe.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"
# Our PID owns the shared-tree lock and users/ entries, also when the repair
# below (a child process) takes them.
export TEST_ENV_OWNER_PID=$$
# shellcheck disable=SC1091
source "$SCRIPT_DIR/shared-trees.sh"

SHARED_TREES=( "$WP_CORE_DIR" "$WP_TESTS_DIR" "$WC_CLONE_DIR" )

_cleanup() {
    st_unregister "${SHARED_TREES[@]}"
    st_unlock
    st_release_owned "${SHARED_TREES[@]}"
}
trap _cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP

# ---------------------------------------------------------------------------
# 1. Health-check + granular repair
# 2. Register as a user of the shared trees
# ---------------------------------------------------------------------------
# The health check runs without the lock. Take it to register, and check the
# trees again under it: another run may have started replacing them meanwhile.
attempt=1
while :; do
    bash "$SCRIPT_DIR/check-test-env.sh"
    st_lock "${SHARED_TREES[@]}"
    if st_wc_tree_ok && st_wp_trees_ok; then
        st_register "${SHARED_TREES[@]}"
        st_unlock
        break
    fi
    st_unlock
    if [ "$attempt" -ge 3 ]; then
        echo "❌ The shared WP/WC trees changed under this run 3 times. Try again." >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    echo "ℹ️  Another run changed the shared WP/WC trees. Checking again..."
done

# ---------------------------------------------------------------------------
# 3. This plugin's wp-tests-config.php
# ---------------------------------------------------------------------------
# Written from the untouched sample in the shared test library (or, for a tree
# installed by an older harness, from its wp-tests-config.php). Every value
# this plugin needs is set explicitly, so whatever another plugin wrote into
# the shared copy does not matter. Uploads (and WC logs) go to a per-plugin
# folder, so suites running at the same time don't share files there either.
write_wp_tests_config() {
    local src="${WP_TESTS_DIR%/}/wp-tests-config-sample.php"
    [ -f "$src" ] || src="${WP_TESTS_DIR%/}/wp-tests-config.php"
    local tmp="${TEST_WP_CONFIG}.tmp.$$"
    local esc_core esc_name esc_user esc_pass esc_host
    _sed_esc() { printf '%s' "$1" | sed -e 's/[\\|&]/\\&/g'; }
    esc_core="$(_sed_esc "${WP_CORE_DIR%/}/")"
    esc_name="$(_sed_esc "$TEST_DB_NAME")"
    esc_user="$(_sed_esc "$TEST_DB_USER")"
    esc_pass="$(_sed_esc "$TEST_DB_PASS")"
    esc_host="$(_sed_esc "${TEST_DB_HOST}:${TEST_DB_PORT}")"
    sed -E \
        -e "s|^[[:space:]]*define\([[:space:]]*'ABSPATH'.*$|define( 'ABSPATH', '${esc_core}' );|" \
        -e "s|define\([[:space:]]*'DB_NAME'[[:space:]]*,[[:space:]]*'[^']*'|define( 'DB_NAME', '${esc_name}'|" \
        -e "s|define\([[:space:]]*'DB_USER'[[:space:]]*,[[:space:]]*'[^']*'|define( 'DB_USER', '${esc_user}'|" \
        -e "s|define\([[:space:]]*'DB_PASSWORD'[[:space:]]*,[[:space:]]*'[^']*'|define( 'DB_PASSWORD', '${esc_pass}'|" \
        -e "s|define\([[:space:]]*'DB_HOST'[[:space:]]*,[[:space:]]*'[^']*'|define( 'DB_HOST', '${esc_host}'|" \
        "$src" > "$tmp"
    printf "\n// Added by tests/bin/run-tests.sh: keep uploads and WC logs out of the shared WP core tree.\ndefine( 'UPLOADS', 'wp-content/uploads-%s' );\n" \
        "$WC_TEST_COMPOSE_PROJECT" >> "$tmp"
    local want
    for want in "'ABSPATH', '${WP_CORE_DIR%/}/'" "'DB_NAME', '${TEST_DB_NAME}'" "'DB_USER', '${TEST_DB_USER}'" \
            "'DB_PASSWORD', '${TEST_DB_PASS}'" "'DB_HOST', '${TEST_DB_HOST}:${TEST_DB_PORT}'"; do
        if ! grep -qF "define( ${want}" "$tmp"; then
            echo "❌ Could not write define( ${want} ) into ${TEST_WP_CONFIG} (template: ${src})." >&2
            rm -f "$tmp"
            return 1
        fi
    done
    mv -f "$tmp" "$TEST_WP_CONFIG"
}
write_wp_tests_config
# Read by tests/php/bootstrap.php, which hands it to the WP test bootstrap.
export WP_TESTS_CONFIG_FILE_PATH="$TEST_WP_CONFIG"

# ---------------------------------------------------------------------------
# 4. Run PHPUnit (pass any extra args through, e.g. --filter)
# ---------------------------------------------------------------------------
echo "==> Running PHPUnit (${WC_TEST_COMPOSE_PROJECT}, DB ${TEST_DB_HOST}:${TEST_DB_PORT}, WC ${WC_DEVELOP_DIR}, WP ${WP_CORE_DIR})..."
# Output format. --testdox prints one readable line per test — pleasant for a
# human, but many times PHPUnit's compact progress output, so it floods
# pre-commit hooks, redirected logs and AI-agent context. Resolve it:
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
# whenever it prints a line), which masks a failed suite and makes `composer test`
# exit 0 on red — defeating the point of the command. We enable pipefail and
# capture PHPUnit's own status via PIPESTATUS[0], then exit with it so callers
# and pre-commit hooks see the true result. The status is read in the `|| ...`
# branch on purpose: a bare `|| true` would run `true`, which is itself a
# pipeline and would overwrite PIPESTATUS before we could read it.
set -o pipefail
status=0
"$PROJECT_ROOT/vendor/bin/phpunit" "${fmt[@]}" "$@" 2>&1 \
    | grep -Ev "^(PHP Deprecated|PHP Notice|PHP Warning|Xdebug)" \
    || status="${PIPESTATUS[0]}"
exit "$status"
