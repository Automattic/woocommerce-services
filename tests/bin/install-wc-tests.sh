#!/usr/bin/env bash
# install-wc-tests.sh
#
# Repairs the WordPress + WooCommerce test environment for PHPUnit.
#
# Normally invoked indirectly by check-test-env.sh, which sets only the
# REPAIR_* env vars for components that probed unhealthy. Each repair function
# is independent and is only run when its flag is set, so the cross-plugin
# /tmp drift case (wrong DB port baked into wp-tests-config.php after another
# extension's setup ran first) is repaired with a single sed — no downloads,
# no svn, no git clone.
#
# Usage:
#   tests/bin/install-wc-tests.sh                # health-check first, repair only what's broken
#   tests/bin/install-wc-tests.sh --force        # reinstall every component
#   REPAIR_WP_CONFIG=1 tests/bin/install-wc-tests.sh   # repair only the wp-tests-config.php DB_HOST
#
# Granular repair flags (env vars, "1" to enable):
#   REPAIR_VENDOR     plugin composer install (vendor/bin/phpunit)
#   REPAIR_WC         re-clone /tmp/woocommerce + composer + feature-config
#   REPAIR_WP         re-install /tmp/wordpress + /tmp/wordpress-tests-lib (svn)
#                     and re-run WC's install.sh to bootstrap the test DB
#   REPAIR_WP_CONFIG  rewrite /tmp/wordpress-tests-lib/wp-tests-config.php DB_HOST
#                     in place (fast path; no downloads)
#   REPAIR_DB         docker compose down -v + up -d (wipes DB volume)
#   REPAIR_DB_PORT    wait for DB to accept TCP connections (no rebuild)

set -e

trap 'echo "❌ install-wc-tests.sh failed on line $LINENO."; exit 1' ERR

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/tests/docker-compose.yml"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"

# Parse flags. With --force, repair everything. With no flags and no
# REPAIR_DISPATCHED sentinel, defer to the health check first — it will exec
# back here with the right REPAIR_* set, only if something is actually broken.
if [[ "${1:-}" == "--force" ]]; then
    shift
    REPAIR_VENDOR=1
    REPAIR_WC=1
    REPAIR_WP=1
    REPAIR_WP_CONFIG=1
    REPAIR_DB=1
    REPAIR_DB_PORT=1
elif [[ -z "${REPAIR_DISPATCHED:-}" \
        && -z "${REPAIR_VENDOR:-}${REPAIR_WC:-}${REPAIR_WP:-}${REPAIR_WP_CONFIG:-}${REPAIR_DB:-}${REPAIR_DB_PORT:-}" ]]; then
    exec env REENTRY_FROM_SETUP=1 bash "$SCRIPT_DIR/check-test-env.sh"
fi

: "${REPAIR_VENDOR:=0}"
: "${REPAIR_WC:=0}"
: "${REPAIR_WP:=0}"
: "${REPAIR_WP_CONFIG:=0}"
: "${REPAIR_DB:=0}"
: "${REPAIR_DB_PORT:=0}"

# Cascades: wiping the DB volume or re-cloning WC means the test DB schema
# must be re-bootstrapped (which also rewrites wp-tests-config.php).
[[ "$REPAIR_DB" == "1" ]] && REPAIR_WP=1
[[ "$REPAIR_WC" == "1" ]] && REPAIR_WP=1
# Re-running WC's install.sh writes a fresh wp-tests-config.php, so an explicit
# WP_CONFIG repair is redundant when REPAIR_WP is set.
[[ "$REPAIR_WP" == "1" ]] && REPAIR_WP_CONFIG=0

WP_VERSION=${EXPECTED_WP_VERSION:-latest}
WC_VERSION=${WC_VERSION:-latest}
DB_HOST="${TEST_DB_HOST}:${TEST_DB_PORT}"

# ---------------------------------------------------------------------------
# mysql / mysqladmin shims — proxy through the DB container so the test env
# is self-contained and does not require a local mysql client. We materialise
# real executables on disk (not shell functions) and prepend their directory
# to PATH, because WC's tests/bin/install.sh uses `which mysqladmin` to verify
# availability — and `which` is external, so it cannot see exported functions.
#
# The shim rewrites connection args so the in-container client always uses TCP
# to 127.0.0.1:3306. Stripping --host/--port/--protocol entirely would force
# the client to a unix socket (a) whose path may not exist in this image and
# (b) whose auth path doesn't match the `test_wp@%` grant created by the
# env-file init. TCP to 127.0.0.1 matches `%` and is always available.
# ---------------------------------------------------------------------------
if ! command -v mysqladmin >/dev/null 2>&1 || ! command -v mysql >/dev/null 2>&1; then
    # Create the shim dir with `mktemp -d`: a unique name owned by the current
    # user, mode 0700. A fixed path under shared /tmp could be pre-created and
    # owned by a hostile local user who could then plant a malicious mysql /
    # mysqladmin that we would execute via the PATH prepend below. Clean it up on
    # exit — the shim is only needed while WC's install.sh runs, not by PHPUnit.
    SHIM_DIR="$(mktemp -d "${TEST_TMP_DIR}/wcservices-mysql-shim.XXXXXX")"
    trap 'rm -rf "$SHIM_DIR"' EXIT
    for _bin in mysqladmin mysql; do
        cat > "$SHIM_DIR/$_bin" <<SHIM
#!/usr/bin/env bash
# Drop the host's --host/--port/--protocol and any -h/-P pairs from the
# caller, then add our own that point at the in-container TCP listener.
args=()
while [ \$# -gt 0 ]; do
    case "\$1" in
        --host=*|--port=*|--protocol=*) shift ;;
        -h?*|-P?*) shift ;;
        --host|--port|--protocol|-h|-P) shift; [ \$# -gt 0 ] && shift ;;
        *) args+=("\$1"); shift ;;
    esac
done
exec docker compose -f "$COMPOSE_FILE" exec -T test_db $_bin \\
    --host=127.0.0.1 --port=3306 --protocol=tcp \\
    \${args[@]+"\${args[@]}"}
SHIM
        chmod +x "$SHIM_DIR/$_bin"
    done
    unset _bin
    export PATH="$SHIM_DIR:$PATH"
fi

echo "============================================================"
echo "Test environment repair"
echo "============================================================"
printf "   %-30s %s\n" "Plugin composer vendor"        "$([[ $REPAIR_VENDOR == 1 ]]    && echo YES || echo skip)"
printf "   %-30s %s\n" "WC source tree"                "$([[ $REPAIR_WC == 1 ]]        && echo YES || echo skip)"
printf "   %-30s %s\n" "WP core + test framework"      "$([[ $REPAIR_WP == 1 ]]        && echo YES || echo skip)"
printf "   %-30s %s\n" "WP tests config DB_HOST"       "$([[ $REPAIR_WP_CONFIG == 1 ]] && echo YES || echo skip)"
printf "   %-30s %s\n" "DB container (wipe volume)"    "$([[ $REPAIR_DB == 1 ]]        && echo YES || echo skip)"
printf "   %-30s %s\n" "DB port wait"                  "$([[ $REPAIR_DB_PORT == 1 ]]   && echo YES || echo skip)"

run_quiet() {
    local label="$1"
    shift
    printf "   %-50s" "$label"
    local log_file="${TEST_TMP_DIR}/wc-test-setup.log"
    if "$@" > "$log_file" 2>&1; then
        echo "✅"
    else
        echo "❌"
        echo "   --- last 20 lines of $log_file ---"
        tail -20 "$log_file"
        return 1
    fi
}

download() {
    if command -v curl &>/dev/null; then
        curl -sSL "$1" > "$2"
    elif command -v wget &>/dev/null; then
        wget -nv -O "$2" "$1"
    else
        echo "Need curl or wget to download $1" >&2
        return 1
    fi
}

# ---------------------------------------------------------------------------
# DB container
# ---------------------------------------------------------------------------
if [[ "$REPAIR_DB" == "1" ]]; then
    echo
    echo "▶️  Recreating DB container (wiping volume to avoid stale state)..."
    docker compose -f "$COMPOSE_FILE" down -v --remove-orphans 2>/dev/null || true
    docker compose -f "$COMPOSE_FILE" up -d
fi

if [[ "$REPAIR_DB" == "1" || "$REPAIR_WP" == "1" || "$REPAIR_DB_PORT" == "1" ]]; then
    docker compose -f "$COMPOSE_FILE" up -d > /dev/null
fi

wait_for_db() {
    local attempts=60
    printf "   %-50s" "Waiting for database at ${TEST_DB_HOST}:${TEST_DB_PORT}..."
    # The TCP port opens before MariaDB finishes initialising the env-file DB
    # schema — wait for an actual ping to succeed, not just for TCP, otherwise
    # the drop-and-recreate dance below races against MariaDB's own init.
    for ((i=1; i<=attempts; i++)); do
        if mysqladmin ping --user="$TEST_DB_USER" --password="$TEST_DB_PASS" \
                --host="$TEST_DB_HOST" --port="$TEST_DB_PORT" --protocol=tcp \
                --silent > /dev/null 2>&1; then
            echo "✅ (after ${i}s)"
            return 0
        fi
        sleep 1
    done
    echo "❌ (timed out after ${attempts}s)"
    docker compose -f "$COMPOSE_FILE" logs --tail=20 test_db || true
    return 1
}

if [[ "$REPAIR_DB" == "1" || "$REPAIR_WP" == "1" || "$REPAIR_DB_PORT" == "1" ]]; then
    wait_for_db
fi

# ---------------------------------------------------------------------------
# Plugin composer dependencies
# ---------------------------------------------------------------------------
if [[ "$REPAIR_VENDOR" == "1" ]]; then
    echo
    run_quiet "Installing plugin composer dependencies..." \
        composer install --working-dir="$PROJECT_ROOT" --no-interaction --quiet
fi

# ---------------------------------------------------------------------------
# Resolve WC_VERSION (only when we actually need it)
# ---------------------------------------------------------------------------
resolve_wc_version() {
    if [ -z "$WC_VERSION" ] || [ "$WC_VERSION" = "latest" ]; then
        WC_VERSION=$(curl -s https://api.github.com/repos/woocommerce/woocommerce/releases/latest \
            | grep '"tag_name"' | sed 's/.*"tag_name": *"\([^"]*\)".*/\1/')
        if [ -z "$WC_VERSION" ]; then
            echo "Could not resolve latest WooCommerce version from GitHub API." >&2
            return 1
        fi
        echo "ℹ️  Resolved latest WC version: $WC_VERSION"
    fi
}

resolve_wp_tests_tag() {
    if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
        WP_TESTS_TAG="branches/$WP_VERSION"
    elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
        if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
            WP_TESTS_TAG="tags/${WP_VERSION%??}"
        else
            WP_TESTS_TAG="tags/$WP_VERSION"
        fi
    elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        WP_TESTS_TAG="trunk"
    else
        download https://api.wordpress.org/core/version-check/1.7/ "${TEST_TMP_DIR}/wp-latest.json"
        local LATEST_VERSION
        LATEST_VERSION=$(grep -o '"version":"[^"]*"' "${TEST_TMP_DIR}/wp-latest.json" | head -1 | sed 's/"version":"//;s/"//')
        if [ -z "$LATEST_VERSION" ]; then
            echo "Could not resolve latest WordPress version." >&2
            return 1
        fi
        WP_TESTS_TAG="tags/$LATEST_VERSION"
    fi
}

# ---------------------------------------------------------------------------
# WooCommerce
# ---------------------------------------------------------------------------
install_wc() {
    echo
    echo "------------------------------------------------------------"
    echo "Installing WooCommerce $WC_VERSION → $WC_CLONE_DIR"
    echo "------------------------------------------------------------"

    rm -rf "$WC_CLONE_DIR"

    run_quiet "Cloning WooCommerce..." \
        git clone --depth=1 --branch="$WC_VERSION" https://github.com/woocommerce/woocommerce.git "$WC_CLONE_DIR" \
        || run_quiet "Cloning WooCommerce (fallback to default branch)..." \
            git clone --depth=1 https://github.com/woocommerce/woocommerce.git "$WC_CLONE_DIR"

    if [ -f "$WC_DEVELOP_DIR/composer.json" ]; then
        run_quiet "Running WC composer install..." \
            composer install --working-dir="$WC_DEVELOP_DIR" --no-interaction --no-progress --quiet
    fi

    local feature_config="${WC_DEVELOP_DIR}/includes/react-admin/feature-config.php"
    if [ ! -f "$feature_config" ] && [ -f "${WC_DEVELOP_DIR}/bin/generate-feature-config.php" ]; then
        run_quiet "Generating WC feature-config.php..." \
            php "${WC_DEVELOP_DIR}/bin/generate-feature-config.php"
    fi
}

# ---------------------------------------------------------------------------
# WordPress core + test suite
# ---------------------------------------------------------------------------
drop_test_db() {
    mysqladmin drop "$TEST_DB_NAME" \
        --user="$TEST_DB_USER" --password="$TEST_DB_PASS" \
        --host="$TEST_DB_HOST" --port="$TEST_DB_PORT" --protocol=tcp \
        --force 2>/dev/null || true
}

# Idempotent DB create. We create the DB ourselves (CREATE IF NOT EXISTS) and
# then pass skip-database-creation=true to WC's install.sh — that way a stale
# database from a prior aborted run can never wedge the repair on "database
# exists". WC install.sh still bootstraps WP schema into the existing DB.
ensure_test_db() {
    mysql --user="$TEST_DB_USER" --password="$TEST_DB_PASS" \
        --host="$TEST_DB_HOST" --port="$TEST_DB_PORT" --protocol=tcp \
        -e "CREATE DATABASE IF NOT EXISTS \`${TEST_DB_NAME}\`;"
}

install_wp_core() {
    if [ -d "$WP_CORE_DIR" ] && [ -f "$WP_CORE_DIR/wp-includes/version.php" ]; then
        return
    fi

    rm -rf "$WP_CORE_DIR"
    mkdir -p "$WP_CORE_DIR"

    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        download https://wordpress.org/nightly-builds/wordpress-latest.zip "${TEST_TMP_DIR}/wordpress.zip"
    elif [[ "$WP_VERSION" == 'latest' ]]; then
        download https://wordpress.org/latest.zip "${TEST_TMP_DIR}/wordpress.zip"
    else
        download "https://wordpress.org/wordpress-$WP_VERSION.zip" "${TEST_TMP_DIR}/wordpress.zip"
    fi

    rm -rf "${TEST_TMP_DIR}/wordpress-extract"
    unzip -q "${TEST_TMP_DIR}/wordpress.zip" -d "${TEST_TMP_DIR}/wordpress-extract"
    mv "${TEST_TMP_DIR}/wordpress-extract/wordpress/"* "$WP_CORE_DIR"
    rm -rf "${TEST_TMP_DIR}/wordpress-extract" "${TEST_TMP_DIR}/wordpress.zip"
}

install_wp_test_suite() {
    if [ ! -d "$WP_TESTS_DIR/includes" ]; then
        mkdir -p "$WP_TESTS_DIR"
        svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
        svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
    fi

    # Always (re)write the config so DB_HOST tracks our compose port.
    download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
    local sed_in_place=( -i )
    [[ "$(uname -s)" == 'Darwin' ]] && sed_in_place=( -i .bak )
    sed "${sed_in_place[@]}" "s:dirname( __FILE__ ) . '/src/':'${WP_CORE_DIR%/}/':" "$WP_TESTS_DIR/wp-tests-config.php"
    sed "${sed_in_place[@]}" "s/youremptytestdbnamehere/$TEST_DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed "${sed_in_place[@]}" "s/yourusernamehere/$TEST_DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed "${sed_in_place[@]}" "s/yourpasswordhere/$TEST_DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed "${sed_in_place[@]}" "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
    # if/fi (not `[[ ]] && rm`): a false `[[ ]]` would make this the function's
    # non-zero return and abort the `[[ REPAIR_WP ]] && install_wp` dispatch.
    if [[ "$(uname -s)" == 'Darwin' ]]; then rm -f "$WP_TESTS_DIR/wp-tests-config.php.bak"; fi
}

install_wp() {
    echo
    echo "------------------------------------------------------------"
    echo "Installing WordPress $WP_VERSION → $WP_CORE_DIR"
    echo "------------------------------------------------------------"

    resolve_wp_tests_tag

    install_wp_core
    install_wp_test_suite

    # Recreate the test database so install.sh's schema bootstrap is clean.
    # Drop is best-effort (may fail if DB is missing or locked); the explicit
    # CREATE IF NOT EXISTS that follows makes the path idempotent regardless.
    drop_test_db
    ensure_test_db

    if [ -f "${WC_DEVELOP_DIR}/tests/bin/install.sh" ]; then
        # Pass skip-database-creation=true (6th arg) — we just created the DB
        # ourselves above, so WC's install.sh must not try `mysqladmin create`
        # again (it would fail with "database exists").
        run_quiet "Running WC install.sh (DB schema)..." \
            bash "${WC_DEVELOP_DIR}/tests/bin/install.sh" \
                "$TEST_DB_NAME" "$TEST_DB_USER" "$TEST_DB_PASS" "$DB_HOST" "$WP_VERSION" "true"
    else
        echo "❌ WC install.sh missing at ${WC_DEVELOP_DIR}/tests/bin/install.sh — cannot bootstrap WP test schema."
        return 1
    fi
}

# ---------------------------------------------------------------------------
# wp-tests-config.php fast repair (sed only)
# ---------------------------------------------------------------------------
repair_wp_config() {
    local cfg="${WP_TESTS_DIR}/wp-tests-config.php"
    if [ ! -f "$cfg" ]; then
        echo "❌ $cfg not found — escalating to full WP repair." >&2
        REPAIR_WP=1
        return 0
    fi
    echo
    printf "   %-50s" "Rewriting wp-tests-config.php DB_HOST..."
    local sed_in_place=( -i )
    [[ "$(uname -s)" == 'Darwin' ]] && sed_in_place=( -i .bak )
    sed "${sed_in_place[@]}" -E \
        "s|define\(\s*'DB_HOST'\s*,\s*'[^']*'\s*\)|define( 'DB_HOST', '${DB_HOST}' )|" "$cfg"
    if [[ "$(uname -s)" == 'Darwin' ]]; then rm -f "${cfg}.bak"; fi
    if grep -qE "define\(\s*'DB_HOST'\s*,\s*'${TEST_DB_HOST}:${TEST_DB_PORT}'\s*\)" "$cfg"; then
        echo "✅ → ${DB_HOST}"
    else
        echo "❌ — sed did not rewrite the line; escalating to full WP repair."
        REPAIR_WP=1
    fi
}

# ---------------------------------------------------------------------------
# Component dispatch (cheapest first)
# ---------------------------------------------------------------------------
[[ "$REPAIR_WP_CONFIG" == "1" ]] && repair_wp_config
[[ "$REPAIR_WC" == "1" ]] && { resolve_wc_version; install_wc; }
[[ "$REPAIR_WP" == "1" ]] && install_wp

echo
echo "🟢 Test environment repair complete."
