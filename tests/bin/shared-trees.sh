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
# the PID is reused. Every copy of this file must compute it the same way,
# and the value must not depend on the caller's TZ, locale or wall clock: a
# run's label is read by other runs, which can have another TZ or LC_ALL.
#   Linux:     field 22 of /proc/<pid>/stat (clock ticks since boot). A clock
#              step (NTP, the WSL resync after sleep) does not change it.
#   elsewhere: `ps -o lstart=` with LC_ALL=C TZ=UTC0 (macOS has no /proc).
# Prints nothing when the start time cannot be read.
# TEST_ENV_START_SOURCE=proc|ps forces one method (used by the self-test).
if [ -z "${TEST_ENV_START_SOURCE:-}" ]; then
    if [ -r /proc/self/stat ]; then TEST_ENV_START_SOURCE="proc"; else TEST_ENV_START_SOURCE="ps"; fi
fi
_st_pid_start() {
    local stat
    if [ "$TEST_ENV_START_SOURCE" = proc ]; then
        stat="$(cat "/proc/$1/stat" 2> /dev/null)" || return 0
        # Field 2 (the process name) can hold spaces and ")": cut after the
        # last ") ". Field 22 is then the 20th field.
        printf '%s\n' "${stat##*) }" | awk '{ if ($20 != "") print "proc:" $20 }'
    else
        LC_ALL=C TZ=UTC0 ps -o lstart= -p "$1" 2> /dev/null | tr -s ' ' | sed 's/^ //; s/ $//'
    fi
}

# Does a process with this PID exist? Used only when its start time cannot be
# read. kill -0 fails for other users' processes, so also look in /proc and ps.
_st_pid_exists() {
    kill -0 "$1" 2> /dev/null && return 0
    [ -d "/proc/$1" ] && return 0
    [ -n "$(ps -o pid= -p "$1" 2> /dev/null)" ]
}

TEST_ENV_OWNER_START="${TEST_ENV_OWNER_START:-$(_st_pid_start "$TEST_ENV_OWNER_PID")}"
export TEST_ENV_OWNER_START

_st_label() {
    printf '%s|%s|%s|%s' "$TEST_ENV_OWNER_PID" "${TEST_ENV_PROJECT:-unknown}" \
        "$(date '+%Y-%m-%d %H:%M:%S')" "$TEST_ENV_OWNER_START"
}

# _st_alive "pid|project|time|start" → 0 if that exact process still runs.
# If a start time is unknown (the label has none, or it cannot be read for the
# PID now), the PID alone decides, and a live PID counts as alive: waiting for
# a stale lock is safer than deleting a tree under a running suite.
_st_alive() {
    local label="$1" pid start now
    pid="${label%%|*}"
    start="${label##*|}"
    case "$pid" in '' | *[!0-9]*) return 1 ;; esac
    [ "$start" != "$label" ] || start=""
    now="$(_st_pid_start "$pid")"
    if [ -n "$now" ] && [ -n "$start" ]; then
        [ "$now" = "$start" ]
        return
    fi
    _st_pid_exists "$pid"
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
    local tree="${1%/}" lock waited=0 next_note=0 owner err
    lock="${tree}.lock"
    if ! err="$(mkdir -p "$(dirname "$tree")" 2>&1)"; then
        echo "❌ Cannot create the folder for ${lock}: ${err}" >&2
        return 1
    fi
    while :; do
        # A lock is always a symlink. `ln -s` would put a link inside an
        # existing folder and report success, so check this first.
        if [ -e "$lock" ] && [ ! -L "$lock" ]; then
            echo "❌ ${lock} exists but is not a lock (a symlink). If no test run uses ${tree}, remove it: rm -rf '${lock}'" >&2
            return 1
        fi
        err="$(ln -s "$(_st_label)" "$lock" 2>&1)" && break
        owner="$(readlink "$lock" 2> /dev/null)" || owner=""
        if [ -z "$owner" ]; then
            # No lock to read. Either its holder released it between our ln
            # and readlink, or ln failed for another reason (for example a
            # folder we cannot write to). Try once more. If there is still no
            # lock, stop with ln's error instead of spinning.
            if [ ! -L "$lock" ] && [ ! -e "$lock" ]; then
                err="$(ln -s "$(_st_label)" "$lock" 2>&1)" && break
                if [ ! -L "$lock" ] && [ ! -e "$lock" ]; then
                    echo "❌ Cannot create ${lock}: ${err}" >&2
                    return 1
                fi
            fi
            continue
        fi
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

# Every tree this harness creates holds this file. st_rm_tree deletes only
# folders that have it, so a mistyped or inherited WP_TESTS_DIR / WP_CORE_DIR /
# WC_CLONE_DIR (for example someone's own WooCommerce checkout) is never
# deleted. Trees are built in a temporary folder, marked, and then renamed into
# place, so a half-done install never sits at the tree's path unmarked.
ST_TREE_MARKER=".wc-test-env-tree"

# st_mark_tree <dir> — mark <dir> as a tree this harness made.
st_mark_tree() {
    printf 'Made by tests/bin/install-wc-tests.sh (escargot-dev-environment tools/phpunit-test-env). Safe to delete.\n' \
        > "${1%/}/${ST_TREE_MARKER}"
}

# st_rm_tree <dir> — delete a shared tree, but only if it is missing, empty,
# or has the marker file.
st_rm_tree() {
    local dir="${1%/}"
    if [ -z "$dir" ] || [ "$dir" = "/" ] || [ "$dir" = "${HOME%/}" ]; then
        echo "❌ Refusing to delete '${dir}'." >&2
        return 1
    fi
    if [ ! -e "$dir" ] || [ -L "$dir" ] || [ -z "$(ls -A "$dir" 2> /dev/null)" ] || [ -f "${dir}/${ST_TREE_MARKER}" ]; then
        rm -rf "$dir"
        return 0
    fi
    echo "❌ Refusing to delete ${dir}: it has no ${ST_TREE_MARKER} file, so this harness did not make it." >&2
    echo "   If it is a test tree from an older harness, delete it yourself and run again: rm -rf '${dir}'" >&2
    echo "   If it is your own checkout, point WP_CORE_DIR / WP_TESTS_DIR / WC_CLONE_DIR somewhere else." >&2
    return 1
}

# Health of the shared trees. Used by check-test-env.sh (no lock), by
# install-wc-tests.sh (under lock, to skip a repair another run just did) and
# by run-tests.sh (under lock, before registering).
st_wc_tree_ok() {
    [ -f "${WC_DEVELOP_DIR}/tests/legacy/bootstrap.php" ] \
        && [ -f "${WC_DEVELOP_DIR}/vendor/autoload.php" ] \
        && [ -f "${WC_DEVELOP_DIR}/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php" ] \
        && [ -f "${WC_DEVELOP_DIR}/includes/react-admin/feature-config.php" ]
}

st_wp_trees_ok() {
    [ -f "${WP_CORE_DIR%/}/wp-includes/version.php" ] \
        && [ -f "${WP_TESTS_DIR%/}/includes/functions.php" ] \
        && { [ -f "${WP_TESTS_DIR%/}/wp-tests-config-sample.php" ] || [ -f "${WP_TESTS_DIR%/}/wp-tests-config.php" ]; }
}
