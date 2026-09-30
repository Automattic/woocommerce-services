#!/usr/bin/env bash
#
# Decides whether a Plugin Check run passes.
#
# Usage: plugin-check-warnings.sh <results-file> <allow-list>
#
# <results-file> is what WordPress/plugin-check-action writes: a "FILE: <path>"
# line followed by a JSON array of findings, repeated for each file.
#
# Any error fails. A warning fails unless the allow-list holds a matching
# "<code> <path>" line, so a code accepted in one file is still reported in
# another. Blank lines and lines starting with "#" are ignored, and entries that
# no longer fire are reported without failing.

set -euo pipefail

RESULTS_FILE="$1"
ALLOW_LIST="$2"

python3 - "$RESULTS_FILE" "$ALLOW_LIST" <<'PY'
import json, re, sys

results_file, allow_list = sys.argv[1], sys.argv[2]

findings = []
blocks = re.split( r'^FILE:\s*(.+)$', open( results_file, errors='replace' ).read(), flags=re.M )

for index in range( 1, len( blocks ), 2 ):
    path = blocks[ index ].strip()
    body = blocks[ index + 1 ]
    start = body.find( '[' )
    if start == -1:
        print( 'No findings array for {}.'.format( path ), file=sys.stderr )
        sys.exit( 1 )
    try:
        # Decode from the opening bracket and ignore whatever follows, so a
        # trailing line after the last array cannot drop that file's findings.
        items, _ = json.JSONDecoder().raw_decode( body[ start: ] )
    except ValueError:
        print( 'Could not read the findings for {}.'.format( path ), file=sys.stderr )
        sys.exit( 1 )
    for item in items:
        findings.append( {
            'severity': str( item.get( 'type', '' ) ).upper(),
            'code': item.get( 'code', '' ),
            'path': path,
            'line': item.get( 'line', 0 ),
            'message': str( item.get( 'message', '' ) ).strip(),
        } )

allowed = {
    line.strip()
    for line in open( allow_list )
    if line.strip() and not line.lstrip().startswith( '#' )
}

errors = [ f for f in findings if f['severity'] == 'ERROR' ]
warnings = [ f for f in findings if f['severity'] == 'WARNING' ]

reported = { '{} {}'.format( f['code'], f['path'] ) for f in warnings }
unexpected = reported - allowed
stale = sorted( allowed - reported )

def show( items ):
    for finding in items:
        print( '  {}:{} {}'.format( finding['path'], finding['line'], finding['code'] ) )
        print( '    {}'.format( finding['message'] ) )

if stale:
    print( 'These allow-list entries no longer fire and can be removed from {}:'.format( allow_list ) )
    for entry in stale:
        print( '  {}'.format( entry ) )
    print()

failed = False

if errors:
    print( 'Plugin Check reported {} error(s), which are never allow-listed:'.format( len( errors ) ) )
    show( errors )
    print()
    failed = True

if unexpected:
    print( 'Plugin Check reported warnings that are not in {}:'.format( allow_list ) )
    show( [ f for f in warnings if '{} {}'.format( f['code'], f['path'] ) in unexpected ] )
    print()
    print( 'Fix the warning, or add the entry to the allow-list with a reason.' )
    failed = True

if failed:
    sys.exit( 1 )

print(
    'Plugin Check passed: no errors, and {} warning(s), all of them allow-listed.'.format( len( warnings ) )
)
PY
