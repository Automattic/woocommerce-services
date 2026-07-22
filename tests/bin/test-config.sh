#!/usr/bin/env bash
# test-config.sh
#
# Single source of truth for test environment configuration.
# Sourced by run-tests.sh, install-wc-tests.sh and check-test-env.sh.
#
# DB credentials come from tests/test.env (the same file mariadb itself reads
# via `env_file:` in tests/docker-compose.yml — defaults can never drift from
# what the container accepts). The DB port is parsed from the published port in
# tests/docker-compose.yml ("4416:3306" → 4416).
#
# /tmp paths are intentionally shared across woocommerce extension plugins
# (USPS, ShipStation, Woo Tax, etc.) so /tmp/wordpress, /tmp/wordpress-tests-lib
# and /tmp/woocommerce only need to be downloaded once. The only file that's
# truly plugin-specific is wp-tests-config.php (it bakes in DB_HOST including the
# port) — check-test-env.sh detects drift on that file and triggers a sed-only
# fast repair.

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
TEST_DB_PORT="$(grep -oE '[0-9]+:3306' "$__tc_compose_file" 2>/dev/null | head -1 | cut -d: -f1)"
TEST_DB_PORT="${TEST_DB_PORT:-4416}"

# Shared /tmp paths (intentionally not plugin-specific — see header).
TEST_TMP_DIR="${TMPDIR:-/tmp}"
TEST_TMP_DIR="${TEST_TMP_DIR%/}"
export WP_CORE_DIR="${WP_CORE_DIR:-${TEST_TMP_DIR}/wordpress}"
export WP_TESTS_DIR="${WP_TESTS_DIR:-${TEST_TMP_DIR}/wordpress-tests-lib}"
export WC_CLONE_DIR="${WC_CLONE_DIR:-${TEST_TMP_DIR}/woocommerce}"
export WC_DEVELOP_DIR="${WC_DEVELOP_DIR:-${WC_CLONE_DIR}/plugins/woocommerce}"

export TEST_DB_NAME TEST_DB_USER TEST_DB_PASS TEST_DB_HOST TEST_DB_PORT

unset __tc_dir __tc_env_file __tc_compose_file
