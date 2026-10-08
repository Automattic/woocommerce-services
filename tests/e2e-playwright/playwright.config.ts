import { defineConfig, type ReporterDescription } from '@playwright/test';
import {
	ADMIN_STORAGE_STATE_PATH,
	OUTPUT_ROOT_PATH,
	REPORT_PATH,
	TESTS_RESULTS_PATH,
} from './utils/paths';
import path from 'node:path';

const { BASE_URL, CI } = process.env;
// QIT injects the environment's URL as QIT_BASE_URL / QIT_SITE_URL (see the
// qit-cli EnvironmentVars::get_mapping mapping), not BASE_URL. An explicit
// BASE_URL still wins, so local runs and manual overrides are unchanged; the
// QIT vars are the CI fallback ahead of the local wp-env default.
//
// `||`, not `??`: these must fall through on an EMPTY string, not only on
// undefined. `test:e2e-playwright:local` resolves the port with `${BASE_URL:-$(node
// bin/resolve-base-url.js)}`, and that script deliberately THROWS on a
// malformed .wp-env.json - a throwing command substitution yields '', which
// `??` would accept. baseURL would then become '/' and every goto() would die
// on an opaque invalid-URL error instead of the clear parse error the script
// went out of its way to raise.
const resolvedBaseUrl =
	BASE_URL ||
	process.env.QIT_BASE_URL ||
	process.env.QIT_SITE_URL ||
	'http://localhost:8888';

// QIT sets QIT=1 in the run environment and collects results.ctrf-json /
// results.blob-dir from qit-test.json - those reporters are REQUIRED there. They
// are gated behind QIT=1 for two reasons: (1) local runs should not emit CTRF /
// blob artefacts (they exist only for QIT's collector), and (2) the CTRF reporter
// is a devDependency of THIS test package, so a local run started before
// `test:e2e-playwright:install` has populated tests/e2e-playwright/node_modules cannot die on a
// "cannot find module 'playwright-ctrf-json-reporter'" load error.
const isQitRun = process.env.QIT === '1';

// The CI env var does not propagate into the QIT test container, so a QIT run
// on a GitHub runner would otherwise execute with the short local timeouts
// and no retries on much slower hardware. Treat QIT runs as CI.
const isCi = Boolean( CI ) || isQitRun;

const reporters: ReporterDescription[] = [
	[ 'list' ],
	[
		'html',
		{
			outputFolder: REPORT_PATH,
			open: 'never',
		},
	],
];

if ( isQitRun ) {
	reporters.push(
		[
			'playwright-ctrf-json-reporter',
			{
				outputDir: OUTPUT_ROOT_PATH,
				outputFile: 'ctrf.json',
			},
		],
		[ 'blob', { outputDir: path.resolve( OUTPUT_ROOT_PATH, 'blob' ) } ]
	);
}

const setupProjects = [
	{
		name: 'global authentication',
		testDir: path.resolve( __dirname, './fixtures' ),
		testMatch: 'auth.setup.ts',
		// A cold wp-env can take far longer than the 10s local action/navigation
		// timeouts to serve its first response (containers still warming up),
		// which fails this login-and-store-session setup once before wp-env is
		// fully up - then passes in ~1-2s on every following run. Give this
		// project its own retries, which replace the global `retries` below
		// (0 locally, 1 in CI) for this project only, so a single cold-start
		// flake here doesn't fail every spec that depends on it.
		retries: isCi ? 2 : 1,
	},
];

export default defineConfig( {
	testDir: './tests',
	outputDir: TESTS_RESULTS_PATH,
	// Disarm the stub once the run is over, so a local store is not left showing
	// fabricated tax amounts. Fail-soft by design - see the file's header.
	globalTeardown: path.resolve( __dirname, './fixtures/global-teardown.ts' ),
	fullyParallel: false,
	forbidOnly: isCi,
	retries: isCi ? 1 : 0,
	workers: 1,
	timeout: 120 * 1000,
	expect: {
		timeout: isCi ? 30 * 1000 : 10 * 1000,
	},
	reporter: reporters,
	use: {
		baseURL: `${ resolvedBaseUrl }/`.replace( /\/+$/, '/' ),
		trace: isCi ? 'on-first-retry' : 'retain-on-failure',
		video: 'retain-on-failure',
		screenshot: 'only-on-failure',
		actionTimeout: isCi ? 30 * 1000 : 10 * 1000,
		navigationTimeout: isCi ? 30 * 1000 : 10 * 1000,
	},
	projects: [
		...setupProjects,
		{
			name: 'e2e',
			dependencies: [ 'global authentication' ],
			use: {
				storageState: ADMIN_STORAGE_STATE_PATH,
			},
		},
	],
} );
