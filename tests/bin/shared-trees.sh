# shellcheck shell=bash
# shared-trees.sh
#
# Coordinates PHPUnit runs of several WooCommerce extensions that share the
# large WordPress / WP test library / WooCommerce trees under $TMPDIR.
#
# Why: the trees are big (WooCommerce with vendor/ is ~3 GB), so every
# extension reuses the same copy. What must NOT be shared is per-plugin state:
# each plugin has its own DB container and run-tests.sh writes its own
# wp-tests-config.php (see TEST_WP_CONFIG in test-config.sh). So test runs
# never wait for each other. Only a change to a shared tree (first install,
# repair, --force) needs coordination:
#
#   <tree>.lock         Symlink. Its target is "pid|project|time|process start".
#                       Held while a run checks or changes <tree>. `ln -s` is
#                       atomic, so the owner is known as soon as the lock exists.
#   <tree>.users/<pid>  One file per test run that is using <tree> right now.
#
#   Test run:  lock its trees, check they are healthy, add users/<pid>, unlock
#              (milliseconds). Then run PHPUnit. Remove users/<pid> on exit.
#   Repair:    lock its trees. Before it deletes or replaces a tree, wait until
#              no other live run is in <tree>.users/. Unlock when done.
#
# A run that wants a lock someone else holds prints who holds it and waits in
# the queue. Locks are always taken in sorted path order, so runs that share
# some trees but not others (different WC or WP versions) cannot deadlock.
# A lock or users/ entry whose process is gone is removed automatically.
#
# IMPORTANT: this file must be identical in every repository that shares the
# trees. A copy with a different protocol does not see these locks.
#
# Bash 3.2 compatible (macOS /bin/bash). No flock(1): macOS does not ship it.

TEST_ENV_LOCK_TIMEOUT="${TEST_ENV_LOCK_TIMEOUT:-1800}"
TEST_ENV_LOCK_POLL="${TEST_ENV_LOCK_POLL:-2}"
TEST_ENV_LOCK_NOTE_EVERY="${TEST_ENV_LOCK_NOTE_EVERY:-60}"

# The process that owns locks and users/ entries. run-tests.sh exports its own
# PID, so a repair it starts (a child process) works on its behalf, and the
# entries live exactly as long as the test run.
TEST_ENV_OWNER_PID="${TEST_ENV_OWNER_PID:-$$}"
export TEST_ENV_OWNER_PID

_ST_HELD=""

# Start time of a process. With the PID it identifies the process even after
# the PID is reused. `ps -o lstart=` works on Linux and macOS, and for other
# users' processes (kill -0 does not).
_st_pid_start() {
    ps -o lstart= -p "$1" 2> /dev/null | tr -s ' ' | sed 's/^ //; s/ $//'
}

TEST_ENV_OWNER_START="${TEST_ENV_OWNER_START:-$(_st_pid_start "$TEST_ENV_OWNER_PID")}"
export TEST_ENV_OWNER_START

_st_label() {
    printf '%s|%s|%s|%s' "$TEST_ENV_OWNER_PID" "${TEST_ENV_PROJECT:-unknown}" \
        "$(date '+%Y-%m-%d %H:%M:%S')" "$TEST_ENV_OWNER_START"
}

# _st_alive "pid|project|time|start" → 0 if that exact process still runs.
_st_alive() {
    local label="$1" pid start rest
    pid="${label%%|*}"
    start="${label##*|}"
    [ -n "$pid" ] || return 1
    rest="$(_st_pid_start "$pid")"
    [ -n "$rest" ] && [ "$rest" = "$start" ]
}

# _st_describe "pid|project|time|start" → "project (pid 123, since time)"
_st_describe() {
    local label="$1" pid project since rest
    pid="${label%%|*}"
    rest="${label#*|}"
    project="${rest%%|*}"
    rest="${rest#*|}"
    since="${rest%%|*}"
    printf '%s (pid %s, since %s)' "$project" "$pid" "$since"
}

_st_lock_one() {
    local tree="${1%/}" lock waited=0 next_note=0 owner
    lock="${tree}.lock"
    mkdir -p "$(dirname "$tree")"
    while ! ln -s "$(_st_label)" "$lock" 2> /dev/null; do
        owner="$(readlink "$lock" 2> /dev/null)" || owner=""
        # Released between our ln and readlink: try again at once.
        [ -n "$owner" ] || continue
        if [ "${owner%%|*}" = "$TEST_ENV_OWNER_PID" ] && [ "${owner##*|}" = "$TEST_ENV_OWNER_START" ]; then
            # Already ours (a repair started by our own run-tests.sh). The
            # holder that took it releases it, so do not add it to _ST_HELD.
            return 0
        fi
        if ! _st_alive "$owner"; then
            # Remove only the lock we looked at. If another waiter already
            # broke it and took a new one, the target is different now.
            if [ "$(readlink "$lock" 2> /dev/null)" = "$owner" ]; then
                rm -f "$lock"
                echo "   ℹ️  Removed a stale lock on ${tree}: $(_st_describe "$owner") is no longer running."
            fi
            continue
        fi
        if [ "$waited" -ge "$next_note" ]; then
            echo "⏳ Waiting in queue (${waited}s): $(_st_describe "$owner") is setting up ${tree}."
            next_note=$((next_note + TEST_ENV_LOCK_NOTE_EVERY))
        fi
        if [ "$waited" -ge "$TEST_ENV_LOCK_TIMEOUT" ]; then
            echo "❌ Gave up after ${waited}s waiting for ${lock}, held by $(_st_describe "$owner")." >&2
            echo "   If that run is stuck, stop it: its lock is released when it exits." >&2
            return 1
        fi
        sleep "$TEST_ENV_LOCK_POLL"
        waited=$((waited + TEST_ENV_LOCK_POLL))
    done
    _ST_HELD="${_ST_HELD}${tree}
"
}

# st_lock <tree>... — lock every tree, in sorted order. Waits while another
# run holds one of them.
st_lock() {
    local tree sorted
    sorted="$(printf '%s\n' "$@" | sed 's:/*$::' | sort -u)"
    while IFS= read -r tree; do
        [ -n "$tree" ] || continue
        _st_lock_one "$tree" || return 1
    done <<EOF
$sorted
EOF
}

# st_unlock — release every lock this process took with st_lock.
st_unlock() {
    local tree owner
    while IFS= read -r tree; do
        [ -n "$tree" ] || continue
        owner="$(readlink "${tree}.lock" 2> /dev/null)" || continue
        if [ "${owner%%|*}" = "$TEST_ENV_OWNER_PID" ] && [ "${owner##*|}" = "$TEST_ENV_OWNER_START" ]; then
            rm -f "${tree}.lock"
        fi
    done <<EOF
$_ST_HELD
EOF
    _ST_HELD=""
}

# st_release_owned <tree>... — release locks held in our owner's name, even if
# a child process took them and died before it could release them.
st_release_owned() {
    local tree owner
    for tree in "$@"; do
        tree="${tree%/}"
        owner="$(readlink "${tree}.lock" 2> /dev/null)" || continue
        if [ "${owner%%|*}" = "$TEST_ENV_OWNER_PID" ] && [ "${owner##*|}" = "$TEST_ENV_OWNER_START" ]; then
            rm -f "${tree}.lock"
        fi
    done
}

# st_register <tree>... — record that this run uses the trees. Call it while
# holding their locks, after checking they are healthy.
st_register() {
    local tree
    for tree in "$@"; do
        tree="${tree%/}"
        mkdir -p "${tree}.users"
        _st_label > "${tree}.users/${TEST_ENV_OWNER_PID}"
    done
}

st_unregister() {
    local tree
    for tree in "$@"; do
        rm -f "${tree%/}.users/${TEST_ENV_OWNER_PID}"
    done
}

# st_wait_idle <tree> — wait until no other live test run uses <tree>. Call it
# while holding the tree's lock, right before deleting or replacing the tree.
# New runs cannot register meanwhile, because registering needs the lock.
st_wait_idle() {
    local tree="${1%/}" waited=0 next_note=0 entry label busy
    while :; do
        busy=""
        for entry in "${tree}.users"/*; do
            [ -f "$entry" ] || continue
            [ "${entry##*/}" = "$TEST_ENV_OWNER_PID" ] && continue
            label="$(cat "$entry" 2> /dev/null)" || continue
            if _st_alive "$label"; then
                busy="${busy}      - $(_st_describe "$label")
"
            else
                rm -f "$entry"
            fi
        done
        [ -n "$busy" ] || return 0
        if [ "$waited" -ge "$next_note" ]; then
            echo "⏳ Waiting in queue (${waited}s): ${tree} is in use by running tests. It is replaced when they finish:"
            printf '%s' "$busy"
            next_note=$((next_note + TEST_ENV_LOCK_NOTE_EVERY))
        fi
        if [ "$waited" -ge "$TEST_ENV_LOCK_TIMEOUT" ]; then
            echo "❌ Gave up after ${waited}s waiting for the runs above to finish with ${tree}." >&2
            return 1
        fi
        sleep "$TEST_ENV_LOCK_POLL"
        waited=$((waited + TEST_ENV_LOCK_POLL))
    done
}

# st_rm_tree <dir> <marker>... — delete a shared tree, but only if it looks
# like one this harness made: it must be missing, empty, or contain one of the
# markers. Stops a mistyped or inherited WP_TESTS_DIR / WP_CORE_DIR /
# WC_CLONE_DIR from deleting something else. Pass a marker that an interrupted
# install leaves behind too (".git" for a half-done clone), or the repair
# could never clean up after it.
st_rm_tree() {
    local dir="${1%/}" marker
    shift
    if [ -z "$dir" ] || [ "$dir" = "/" ] || [ "$dir" = "${HOME%/}" ]; then
        echo "❌ Refusing to delete '${dir}'." >&2
        return 1
    fi
    if [ ! -e "$dir" ] || [ -z "$(ls -A "$dir" 2> /dev/null)" ]; then
        rm -rf "$dir"
        return 0
    fi
    for marker in "$@"; do
        if [ -e "${dir}/${marker}" ]; then
            rm -rf "$dir"
            return 0
        fi
    done
    echo "❌ Refusing to delete ${dir}: it is not empty and has none of: $*. It may not be a test tree." >&2
    return 1
}

# Health of the shared trees. Used by check-test-env.sh (no lock), by
# install-wc-tests.sh (under lock, to skip a repair another run just did) and
# by run-tests.sh (under lock, before registering).
st_wc_tree_ok() {
    [ -f "${WC_DEVELOP_DIR}/tests/legacy/bootstrap.php" ] \
        && [ -f "${WC_DEVELOP_DIR}/vendor/autoload.php" ] \
        && [ -f "${WC_DEVELOP_DIR}/includes/react-admin/feature-config.php" ]
}

st_wp_trees_ok() {
    [ -f "${WP_CORE_DIR%/}/wp-includes/version.php" ] \
        && [ -f "${WP_TESTS_DIR%/}/includes/functions.php" ] \
        && { [ -f "${WP_TESTS_DIR%/}/wp-tests-config-sample.php" ] || [ -f "${WP_TESTS_DIR%/}/wp-tests-config.php" ]; }
}
