import path from 'node:path';

// Filesystem paths shared by playwright.config.ts, fixtures, and specs.
// Kept in their own module (instead of exported from playwright.config.ts) so
// specs can import a storage path without re-importing the config module - which
// would re-run defineConfig(), the reporter branching, and the env reads on every
// worker's spec import.
//
// Everything the run produces stays inside the test-package directory: QIT
// bind-mounts this package into the container and collects results from the paths
// declared in qit-test.json (./results/ctrf.json + ./results/blob), so artefacts
// written outside the package would be invisible to QIT and may not be writable in
// the container.
const E2E_ROOT = path.resolve( __dirname, '..' );

export const OUTPUT_ROOT_PATH = path.resolve( E2E_ROOT, 'results' );
export const TESTS_RESULTS_PATH = path.resolve( E2E_ROOT, 'test-results' );
export const REPORT_PATH = path.resolve( E2E_ROOT, 'report' );
export const STORAGE_DIR_PATH = path.resolve( E2E_ROOT, '.state' );
export const ADMIN_STORAGE_STATE_PATH = path.resolve(
	STORAGE_DIR_PATH,
	'admin.json'
);
