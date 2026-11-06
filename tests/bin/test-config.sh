#!/usr/bin/env bash
# test-config.sh
#
# Single source of truth for test environment configuration.
# Sourced by run-tests.sh, install-wc-tests.sh and check-test-env.sh.
#
# DB credentials come from tests/test.env (the same file mariadb itself reads
# via `env_file:` in tests/docker-compose.yml — defaults can never drift from
# what the container accepts). The compose project name and the DB port default
# to the values in tests/docker-compose.yml. Override them to run two checkouts
# of this plugin at the same time:
#   WC_TEST_COMPOSE_PROJECT=myplugin_test2 WC_TEST_DB_PORT=4499 bash tests/bin/run-tests.sh
#
# Shared trees. /tmp/wordpress, /tmp/wordpress-tests-lib and /tmp/woocommerce
# are shared with the other WooCommerce extensions on this machine, so they
# are downloaded once. Nothing plugin-specific is written
# into them: run-tests.sh writes this plugin's own wp-tests-config.php
# (TEST_WP_CONFIG). Changes to the shared trees are coordinated by
# shared-trees.sh, so several plugins can run their suites at the same time.
#
# Versions. With WC_VERSION or EXPECTED_WP_VERSION pinned, the trees for that
# version live in their own folders under $TMPDIR/wc-test-trees/, so suites on
# different versions can run at the same time and the default trees are left
# alone. Unpinned ("latest") runs keep using the default folders above.
# WP_CORE_DIR, WP_TESTS_DIR and WC_CLONE_DIR still override all of this.

__tc_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
__tc_env_file="${__tc_dir}/test.env"
__tc_compose_file="${__tc_dir}/docker-compose.yml"

if [[ -f "$__tc_env_file" ]]; then
    set -a
    # shellcheck disable=SC1090
    source "$__tc_env_file"
    set +a
fi

TEST_DB_NAME="${MYSQL_DATABASE:-test_wp}"
TEST_DB_USER="${MYSQL_USER:-test_wp}"
TEST_DB_PASS="${MYSQL_PASSWORD:-test_wp}"
TEST_DB_HOST="127.0.0.1"

# Defaults from docker-compose.yml: `name: ${WC_TEST_COMPOSE_PROJECT:-x}` → x,
# and the published port `${WC_TEST_DB_PORT:-4426}:3306` → 4426. A literal
# `name: x` / `4426:3306` works too.
__tc_default_project="$(sed -nE 's/^name:[[:space:]]*(\$\{[A-Z_]+:-)?([A-Za-z0-9_.-]+)\}?[[:space:]]*$/\2/p' "$__tc_compose_file" 2>/dev/null | head -1)"
__tc_default_port="$(sed -nE 's/.*[^0-9]([0-9]+)\}?:3306.*/\1/p' "$__tc_compose_file" 2>/dev/null | head -1)"
export WC_TEST_COMPOSE_PROJECT="${WC_TEST_COMPOSE_PROJECT:-$__tc_default_project}"
export WC_TEST_DB_PORT="${WC_TEST_DB_PORT:-$__tc_default_port}"
if [ -z "$WC_TEST_COMPOSE_PROJECT" ] || [ -z "$WC_TEST_DB_PORT" ]; then
    echo "❌ Could not read the compose project name or DB port from ${__tc_compose_file}." >&2
    echo "   Expected 'name: \${WC_TEST_COMPOSE_PROJECT:-<name>}' and '<port>:3306'." >&2
    return 1 2> /dev/null || exit 1
fi
TEST_DB_PORT="$WC_TEST_DB_PORT"
# Label shown to other runs waiting in the queue.
TEST_ENV_PROJECT="$WC_TEST_COMPOSE_PROJECT"

TEST_TMP_DIR="${TMPDIR:-/tmp}"
TEST_TMP_DIR="${TEST_TMP_DIR%/}"

# Folder-safe version name: "release/10.9" → "release_10.9".
__tc_slug() { printf '%s' "$1" | tr -c 'A-Za-z0-9._-' '_'; }

__tc_wp_pin="${EXPECTED_WP_VERSION:-}"
case "$__tc_wp_pin" in latest|stable) __tc_wp_pin="" ;; esac
__tc_wc_pin="${WC_VERSION:-}"
case "$__tc_wc_pin" in latest) __tc_wc_pin="" ;; esac

if [ -n "$__tc_wp_pin" ]; then
    __tc_wp_core_default="${TEST_TMP_DIR}/wc-test-trees/wordpress-$(__tc_slug "$__tc_wp_pin")"
    __tc_wp_tests_default="${TEST_TMP_DIR}/wc-test-trees/wordpress-tests-lib-$(__tc_slug "$__tc_wp_pin")"
else
    __tc_wp_core_default="${TEST_TMP_DIR}/wordpress"
    __tc_wp_tests_default="${TEST_TMP_DIR}/wordpress-tests-lib"
fi
if [ -n "$__tc_wc_pin" ]; then
    __tc_wc_default="${TEST_TMP_DIR}/wc-test-trees/woocommerce-$(__tc_slug "$__tc_wc_pin")"
else
    __tc_wc_default="${TEST_TMP_DIR}/woocommerce"
fi

export WP_CORE_DIR="${WP_CORE_DIR:-$__tc_wp_core_default}"
export WP_TESTS_DIR="${WP_TESTS_DIR:-$__tc_wp_tests_default}"
export WC_CLONE_DIR="${WC_CLONE_DIR:-$__tc_wc_default}"
export WC_DEVELOP_DIR="${WC_DEVELOP_DIR:-${WC_CLONE_DIR}/plugins/woocommerce}"

# Per-plugin files. This plugin's wp-tests-config.php lives in the checkout
# (gitignored), so two checkouts on different DB ports never share one.
TEST_WP_CONFIG="${__tc_dir}/.wp-tests-config.php"
TEST_SETUP_LOG="${TEST_TMP_DIR}/wc-test-setup-${WC_TEST_COMPOSE_PROJECT}.log"

export TEST_DB_NAME TEST_DB_USER TEST_DB_PASS TEST_DB_HOST TEST_DB_PORT
export TEST_ENV_PROJECT TEST_WP_CONFIG TEST_SETUP_LOG

unset __tc_dir __tc_env_file __tc_compose_file __tc_default_project __tc_default_port
unset __tc_wp_pin __tc_wc_pin __tc_wp_core_default __tc_wp_tests_default __tc_wc_default
unset -f __tc_slug
