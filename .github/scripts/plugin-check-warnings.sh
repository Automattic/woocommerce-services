#!/usr/bin/env bash
#
# Fails when Plugin Check reports a warning that is not in the allow-list.
#
# Usage: plugin-check-warnings.sh <qit-result.json> <allow-list>
#
# The allow-list holds one "<code> <path>" pair per line. Blank lines and lines
# starting with "#" are ignored.

set -euo pipefail

RESULT_FILE="$1"
ALLOW_LIST="$2"

# QIT returns the findings as a JSON string in some versions and as an array in others.
reported="$(jq -r '
	.test_result_json
	| if type == "string" then fromjson else . end
	| .[]
	| select( .severity == "WARNING" )
	| "\(.code) \(.path)"
' "$RESULT_FILE" | sort -u)"

allowed="$(grep -Ev '^[[:space:]]*(#|$)' "$ALLOW_LIST" | sed -E 's/[[:space:]]+$//' | sort -u)"

unexpected="$(comm -23 <(echo "$reported") <(echo "$allowed") | sed '/^$/d')"
stale="$(comm -13 <(echo "$reported") <(echo "$allowed") | sed '/^$/d')"

if [ -n "$stale" ]; then
	echo "These allow-list entries no longer fire and can be removed from ${ALLOW_LIST}:"
	echo "$stale" | sed 's/^/  /'
	echo
fi

if [ -n "$unexpected" ]; then
	echo "Plugin Check reported warnings that are not in ${ALLOW_LIST}:"
	echo
	jq -r '
		.test_result_json
		| if type == "string" then fromjson else . end
		| .[]
		| select( .severity == "WARNING" )
		| "\(.code) \(.path)\t\(.path):\(.line)\t\(.message)"
	' "$RESULT_FILE" | while IFS=$'\t' read -r key location message; do
		if echo "$unexpected" | grep -Fxq "$key"; then
			echo "  ${location} ${key%% *}"
			echo "    ${message}"
		fi
	done
	echo
	echo "Fix the warning, or add the entry to the allow-list with a reason."
	exit 1
fi

echo "Plugin Check reported no new warnings."
