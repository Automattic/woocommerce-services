#!/usr/bin/env node
/**
 * Print the local wp-env base URL for the Playwright `test:e2e:local*` scripts.
 *
 * Resolution order (first hit wins):
 *   1. .wp-env.override.json `port` (a developer's local, gitignored override)
 *   2. .wp-env.json `port`          (only if the committed config sets one)
 *   3. 8888                          (wp-env's own default; most .wp-env.json
 *                                     files define no `port`, so this is what a
 *                                     default checkout resolves to)
 *
 * This is repo-level tooling: it reads the repo-root wp-env config and is invoked
 * only by the root package.json `test:e2e:local*` scripts (which run from the repo
 * root). It lives under the test package for colocation with the suite it serves;
 * it is never executed inside the QIT container (where these relative paths would
 * not resolve), and it is excluded from the shipped plugin zip by the `tests`
 * archive rule.
 *
 * An explicit BASE_URL in the environment takes precedence and never reaches this
 * script: the npm scripts use `${BASE_URL:-$(node ...)}`, so this only runs when
 * BASE_URL is unset. Keeping the logic here (not in the npm string) lets a bare
 * `pnpm run test:e2e:local` target whichever port wp-env actually bound, without
 * each developer hard-coding their port.
 */



const fs = require( 'node:fs' );
const path = require( 'node:path' );

const repoRoot = path.resolve( __dirname, '..', '..', '..' );

const readPort = ( file ) => {
	let raw;
	try {
		raw = fs.readFileSync( path.join( repoRoot, file ), 'utf8' );
	} catch ( error ) {
		if ( error.code === 'ENOENT' ) {
			// No such config (e.g. no local override) - fall through to the next.
			return null;
		}
		// A present-but-unreadable file (permissions, etc.) is a real problem, not
		// a "use the default" signal - surface it instead of silently hiding it.
		throw error;
	}

	let config;
	try {
		config = JSON.parse( raw );
	} catch ( error ) {
		// Malformed JSON would otherwise be silently ignored, sending the suite to
		// port 8888 and failing later with a confusing connection error. Fail loud.
		throw new Error( `Could not parse ${ file }: ${ error.message }` );
	}

	return typeof config.port === 'number' ? config.port : null;
};

const port =
	readPort( '.wp-env.override.json' ) ?? readPort( '.wp-env.json' ) ?? 8888;

process.stdout.write( `http://localhost:${ port }` );
