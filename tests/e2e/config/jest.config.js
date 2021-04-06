/**
 * @flow strict
 * @format
 */
const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

// https://jestjs.io/docs/en/configuration.html

module.exports = useE2EJestConfig( {
	// Automatically clear mock calls and instances between every test
	clearMocks: true,

	// An array of file extensions your modules use
	moduleFileExtensions: [ 'js' ],

	preset: 'jest-puppeteer',

	// Where to look for test files
	roots: [ path.resolve( __dirname, '../specs' ) ],

	// The glob patterns Jest uses to detect test files
	testMatch: [ '**/*.(test|spec).js' ],
} );
