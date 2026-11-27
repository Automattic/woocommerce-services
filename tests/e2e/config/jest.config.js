/**
 * @flow strict
 * @format
 */
const path = require( 'path' );
// WordPress utilities start REST requests when imported by the environment.
// Configure their URL and credentials before loading that environment.
const { getTestConfig } = require( '@woocommerce/e2e-environment/utils/test-config' );
const testConfig = getTestConfig();
process.env.WP_BASE_URL = testConfig.baseUrl;
process.env.WP_USERNAME = testConfig.users.admin.username;
process.env.WP_PASSWORD = testConfig.users.admin.password;

const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

// https://jestjs.io/docs/en/configuration.html

module.exports = useE2EJestConfig( {
	// Automatically clear mock calls and instances between every test
	clearMocks: true,

	// An array of file extensions your modules use
	moduleFileExtensions: [ 'js' ],

	preset: 'jest-puppeteer',

	// The legacy environment's precompiled setup uses the global runtime.
	setupFiles: [ '@babel/runtime/regenerator', '<rootDir>/config/env.setup.js' ],

	// Where to look for test files
	roots: [ path.resolve( __dirname, '../specs' ) ],

	// The glob patterns Jest uses to detect test files
	testMatch: [ '**/*.(test|spec).js' ],
} );
