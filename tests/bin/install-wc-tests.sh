#!/usr/bin/env bash
# install-wc-tests.sh
#
# Repairs the WordPress + WooCommerce test environment for PHPUnit.
#
# Normally invoked indirectly by check-test-env.sh, which sets only the
# REPAIR_* env vars for components that probed unhealthy. Each repair function
# is independent and is only run when its flag is set.
#
# Two kinds of state:
#   per plugin   the DB container, the test database, vendor/. Repaired
#                without any lock: nothing else uses them.
#   shared       WP core, the WP test library and the WooCommerce clone, which
#                all extensions on this machine reuse. Repaired under the
#                shared-trees.sh lock. If another run holds the lock, this
#                script prints who and waits in the queue. Before it deletes or
#                replaces a shared tree, it waits until no other running suite
#                uses that tree.
#
# Usage:
#   tests/bin/install-wc-tests.sh                # health-check first, repair only what's broken
#   tests/bin/install-wc-tests.sh --force        # reinstall every component
#
# Version pinning: WC_VERSION defaults to the latest WooCommerce release, which
# can differ from CI's rolling 3-latest-minors matrix. A pinned version gets its
# own trees under $TMPDIR/wc-test-trees/ (see test-config.sh), so it never
# replaces the trees another suite is using:
#   WC_VERSION=9.4.0 composer test
#   EXPECTED_WP_VERSION=6.8 composer test
#
# Granular repair flags (env vars, "1" to enable):
#   REPAIR_VENDOR     plugin composer install (vendor/bin/phpunit)
#   REPAIR_WC         (shared) re-clone WooCommerce + composer + feature-config
#   REPAIR_WP         (shared) install WP core + the WP test library (svn)
#   REPAIR_DB         docker compose down -v + up -d (wipes DB volume)
#   REPAIR_DB_PORT    wait for DB to accept TCP connections (no rebuild)
# Any of REPAIR_WC, REPAIR_WP, REPAIR_DB also drops and recreates this
# plugin's test database.

set -e

trap 'echo "❌ install-wc-tests.sh failed on line $LINENO."; exit 1' ERR

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/tests/docker-compose.yml"

# shellcheck disable=SC1091
source "$SCRIPT_DIR/test-config.sh"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/shared-trees.sh"

# Parse flags. With --force, repair everything. With no flags and no
# REPAIR_DISPATCHED sentinel, defer to the health check first — it will exec
# back here with the right REPAIR_* set, only if something is actually broken.
FORCE=0
if [[ "${1:-}" == "--force" ]]; then
    shift
    FORCE=1
    REPAIR_VENDOR=1
    REPAIR_WC=1
    REPAIR_WP=1
    REPAIR_DB=1
    REPAIR_DB_PORT=1
elif [[ -z "${REPAIR_DISPATCHED:-}" \
        && -z "${REPAIR_VENDOR:-}${REPAIR_WC:-}${REPAIR_WP:-}${REPAIR_DB:-}${REPAIR_DB_PORT:-}" ]]; then
    exec env REENTRY_FROM_SETUP=1 bash "$SCRIPT_DIR/check-test-env.sh"
fi

: "${REPAIR_VENDOR:=0}"
: "${REPAIR_WC:=0}"
: "${REPAIR_WP:=0}"
: "${REPAIR_DB:=0}"
: "${REPAIR_DB_PORT:=0}"

# Recreate this plugin's test database whenever the DB volume or a shared tree
# changes, so the schema the WP test bootstrap installs starts clean.
REPAIR_TEST_DB=0
[[ "$REPAIR_DB" == "1" || "$REPAIR_WC" == "1" || "$REPAIR_WP" == "1" ]] && REPAIR_TEST_DB=1

WP_VERSION=${EXPECTED_WP_VERSION:-latest}
WC_VERSION=${WC_VERSION:-latest}

# ---------------------------------------------------------------------------
# mysql / mysqladmin shims — proxy through the DB container so the test env
# is self-contained and does not require a local mysql client. We materialise
# real executables on disk (not shell functions) and prepend their directory
# to PATH, so any later `which mysqladmin` check finds them too.
#
# The shim rewrites connection args so the in-container client always uses TCP
# to 127.0.0.1:3306. Stripping --host/--port/--protocol entirely would force
# the client to a unix socket (a) whose path may not exist in this image and
# (b) whose auth path doesn't match the `test_wp@%` grant created by the
# env-file init. TCP to 127.0.0.1 matches `%` and is always available.
#
# The shim is used even when the host has its own client. A host client is a
# different version from the server and fails in ways that only show up as a
# 60 s timeout in wait_for_db: MariaDB 11.4+ clients require TLS by default,
# which the container does not offer, and MySQL 8.x clients have an unreliable
# handshake against MariaDB. The container's own client always matches.
# ---------------------------------------------------------------------------
# Create the shim dir with `mktemp -d`: a unique name owned by the current
# user, mode 0700. A fixed path under shared /tmp could be pre-created and
# owned by a hostile local user who could then plant a malicious mysql /
# mysqladmin that we would execute via the PATH prepend below. Clean it up on
# exit, together with any shared-tree lock this script still holds.
mkdir -p "$TEST_TMP_DIR"
SHIM_DIR="$(mktemp -d "${TEST_TMP_DIR}/${WC_TEST_COMPOSE_PROJECT}-mysql-shim.XXXXXX")"
_cleanup() {
    st_unlock
    rm -rf "$SHIM_DIR"
}
trap _cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP
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

echo "============================================================"
echo "Test environment repair (${WC_TEST_COMPOSE_PROJECT})"
echo "============================================================"
printf "   %-30s %s\n" "Plugin composer vendor"        "$([[ $REPAIR_VENDOR == 1 ]]  && echo YES || echo skip)"
printf "   %-30s %s\n" "DB container (wipe volume)"    "$([[ $REPAIR_DB == 1 ]]      && echo YES || echo skip)"
printf "   %-30s %s\n" "DB port wait"                  "$([[ $REPAIR_DB_PORT == 1 ]] && echo YES || echo skip)"
printf "   %-30s %s\n" "Test database (recreate)"      "$([[ $REPAIR_TEST_DB == 1 ]] && echo YES || echo skip)"
printf "   %-30s %s\n" "WC source tree (shared)"       "$([[ $REPAIR_WC == 1 ]]      && echo YES || echo skip)"
printf "   %-30s %s\n" "WP core + tests lib (shared)"  "$([[ $REPAIR_WP == 1 ]]      && echo YES || echo skip)"

run_quiet() {
    local label="$1"
    shift
    printf "   %-50s" "$label"
    if "$@" > "$TEST_SETUP_LOG" 2>&1; then
        echo "✅"
    else
        echo "❌"
        echo "   --- last 20 lines of $TEST_SETUP_LOG ---"
        tail -20 "$TEST_SETUP_LOG"
        return 1
    fi
}

# Download to a temporary name next to the target, then rename: a reader of
# the target never sees a half-written file.
download() {
    local tmp="${2}.part.$$"
    if command -v curl &>/dev/null; then
        curl -fsSL "$1" > "$tmp" || { rm -f "$tmp"; return 1; }
    elif command -v wget &>/dev/null; then
        wget -nv -O "$tmp" "$1" || { rm -f "$tmp"; return 1; }
    else
        echo "Need curl or wget to download $1" >&2
        return 1
    fi
    mv -f "$tmp" "$2"
}

# ===========================================================================
# Per-plugin state (no lock)
# ===========================================================================

# ---------------------------------------------------------------------------
# DB container
# ---------------------------------------------------------------------------
if [[ "$REPAIR_DB" == "1" ]]; then
    echo
    echo "▶️  Recreating DB container (wiping volume to avoid stale state)..."
    docker compose -f "$COMPOSE_FILE" down -v --remove-orphans 2>/dev/null || true
    # A container with our pinned name that `down` could not see belongs to an
    # older compose project of this same plugin (before `name:` was pinned).
    # It would block `up -d` on the name and port, so remove it.
    docker rm -f "${WC_TEST_COMPOSE_PROJECT}_sql" > /dev/null 2>&1 || true
    docker compose -f "$COMPOSE_FILE" up -d
fi

if [[ "$REPAIR_DB" == "1" || "$REPAIR_TEST_DB" == "1" || "$REPAIR_DB_PORT" == "1" ]]; then
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

if [[ "$REPAIR_DB" == "1" || "$REPAIR_TEST_DB" == "1" || "$REPAIR_DB_PORT" == "1" ]]; then
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
# Test database
# ---------------------------------------------------------------------------
drop_test_db() {
    mysqladmin drop "$TEST_DB_NAME" \
        --user="$TEST_DB_USER" --password="$TEST_DB_PASS" \
        --host="$TEST_DB_HOST" --port="$TEST_DB_PORT" --protocol=tcp \
        --force 2>/dev/null || true
}

# Idempotent DB create, so a stale database from a prior aborted run can never
# wedge the repair on "database exists". The WP test bootstrap installs the
# schema into it at the start of every PHPUnit run.
ensure_test_db() {
    mysql --user="$TEST_DB_USER" --password="$TEST_DB_PASS" \
        --host="$TEST_DB_HOST" --port="$TEST_DB_PORT" --protocol=tcp \
        -e "CREATE DATABASE IF NOT EXISTS \`${TEST_DB_NAME}\`;"
}

if [[ "$REPAIR_TEST_DB" == "1" ]]; then
    # Best-effort drop, then the CREATE IF NOT EXISTS makes it idempotent.
    drop_test_db
    ensure_test_db
fi

# ===========================================================================
# Shared trees (under the shared-trees.sh lock)
# ===========================================================================

# ---------------------------------------------------------------------------
# Resolve versions (only when we actually need them)
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
        local latest_json="${WP_CORE_DIR%/}.latest.json" LATEST_VERSION
        download https://api.wordpress.org/core/version-check/1.7/ "$latest_json"
        LATEST_VERSION=$(grep -o '"version":"[^"]*"' "$latest_json" | head -1 | sed 's/"version":"//;s/"//')
        rm -f "$latest_json"
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

    st_wait_idle "$WC_CLONE_DIR"
    st_rm_tree "$WC_CLONE_DIR" "plugins/woocommerce" ".git"

    # No fallback to the default branch: a version that cannot be cloned (a
    # typo, a network failure) must fail the run rather than quietly test
    # WooCommerce trunk under the requested version's name. In CI that trunk
    # copy would also be cached under the release tag's key.
    run_quiet "Cloning WooCommerce $WC_VERSION..." \
        git clone --depth=1 --branch="$WC_VERSION" https://github.com/woocommerce/woocommerce.git "$WC_CLONE_DIR"

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
# WordPress core + test library
# ---------------------------------------------------------------------------
install_wp_core() {
    if [ -f "${WP_CORE_DIR%/}/wp-includes/version.php" ]; then
        return
    fi

    st_wait_idle "$WP_CORE_DIR"
    st_rm_tree "$WP_CORE_DIR" "wp-includes/version.php"

    # Temporary files next to the tree, not at a fixed $TMPDIR path: two runs
    # installing different WP versions at the same time must not share them.
    local zip="${WP_CORE_DIR%/}.download.zip" extract="${WP_CORE_DIR%/}.extract"
    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        download https://wordpress.org/nightly-builds/wordpress-latest.zip "$zip"
    elif [[ "$WP_VERSION" == 'latest' ]]; then
        download https://wordpress.org/latest.zip "$zip"
    else
        download "https://wordpress.org/wordpress-$WP_VERSION.zip" "$zip"
    fi

    rm -rf "$extract"
    unzip -q "$zip" -d "$extract"
    mkdir -p "$(dirname "${WP_CORE_DIR%/}")"
    mv "${extract}/wordpress" "${WP_CORE_DIR%/}"
    rm -rf "$extract" "$zip"
}

install_wp_test_suite() {
    if [ ! -f "${WP_TESTS_DIR%/}/includes/functions.php" ]; then
        st_wait_idle "$WP_TESTS_DIR"
        st_rm_tree "$WP_TESTS_DIR" "includes/functions.php" "includes"
        mkdir -p "$WP_TESTS_DIR"
        svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
        svn co --quiet --ignore-externals "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
    fi

    # The untouched sample is the template for every plugin's own
    # wp-tests-config.php (run-tests.sh). It is never edited in place.
    if [ ! -f "${WP_TESTS_DIR%/}/wp-tests-config-sample.php" ]; then
        download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" \
            "${WP_TESTS_DIR%/}/wp-tests-config-sample.php"
    fi
}

install_wp() {
    echo
    echo "------------------------------------------------------------"
    echo "Installing WordPress $WP_VERSION → $WP_CORE_DIR"
    echo "------------------------------------------------------------"

    resolve_wp_tests_tag

    # The installers below keep a WordPress that is already present. On
    # --force start clean, or EXPECTED_WP_VERSION would be ignored and the
    # previous version tested instead. Wait for running suites first.
    if [[ "$FORCE" == "1" ]]; then
        st_wait_idle "$WP_CORE_DIR"
        st_wait_idle "$WP_TESTS_DIR"
        st_rm_tree "$WP_CORE_DIR" "wp-includes/version.php"
        st_rm_tree "$WP_TESTS_DIR" "includes/functions.php" "includes"
    fi

    install_wp_core
    printf "   %-50s✅\n" "WordPress core"
    install_wp_test_suite
    printf "   %-50s✅\n" "WordPress test library"
}

# ---------------------------------------------------------------------------
# Component dispatch
# ---------------------------------------------------------------------------
if [[ "$REPAIR_WC" == "1" || "$REPAIR_WP" == "1" ]]; then
    echo
    # Lock only the trees this repair changes, so a run on another WC (or WP)
    # version is not held up. All of them in one st_lock call: sorted order.
    lock_trees=()
    [[ "$REPAIR_WC" == "1" ]] && lock_trees+=( "$WC_CLONE_DIR" )
    [[ "$REPAIR_WP" == "1" ]] && lock_trees+=( "$WP_CORE_DIR" "$WP_TESTS_DIR" )
    st_lock "${lock_trees[@]}"
    # The probes ran without the lock. Another run may have finished this
    # repair while we waited, so check again before doing it twice.
    if [[ "$FORCE" != "1" ]]; then
        if [[ "$REPAIR_WC" == "1" ]] && st_wc_tree_ok; then
            echo "   ✅ WC tree is ready (set up by another run). Skipping."
            REPAIR_WC=0
        fi
        if [[ "$REPAIR_WP" == "1" ]] && st_wp_trees_ok; then
            echo "   ✅ WP trees are ready (set up by another run). Skipping."
            REPAIR_WP=0
        fi
    fi
    if [[ "$REPAIR_WC" == "1" ]]; then resolve_wc_version; install_wc; fi
    if [[ "$REPAIR_WP" == "1" ]]; then install_wp; fi
    st_unlock
fi

echo
echo "🟢 Test environment repair complete."
