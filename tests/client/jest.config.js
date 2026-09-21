/** @format */

module.exports = {
	moduleNameMapper: {
		'^config$': '<rootDir>/client/calypso-stubs/config.js',
		"^wcs-client/(.*)$": "<rootDir>/client/$1"
	},
	transform: {
		'^.+\\.jsx?$': '<rootDir>/tests/test/helpers/assets/babel-transform.js',
		'\\.(gif|jpg|jpeg|png|svg|scss|sass|css)$': '<rootDir>/tests/test/helpers/assets/transform.js',
	},
	modulePaths: [
		'<rootDir>/tests/',
		'<rootDir>/client/',
		'<rootDir>/client/calypso-stubs/',
		'<rootDir>/client/calypso-stubs/extensions/',
		'<rootDir>/client/extensions/',
		'<rootDir>/client/calypso'
	],
	rootDir: './../../',
	roots: [ '<rootDir>/client/' ],
	testEnvironment: 'jsdom',
	transformIgnorePatterns: [
		'node_modules[\\/\\\\](?!flag-icon-css|redux-form|simple-html-tokenizer|draft-js)',
	],
	testMatch: [ '<rootDir>/client/**/test/*.js?(x)', '!**/.eslintrc.*' ],
	testURL: 'https://example.com',
	setupFiles: [
		'regenerator-runtime/runtime', // some NPM-published packages depend on the global
		'whatwg-fetch', // webpack supplies `fetch` via ProvidePlugin; jsdom does not
	],
	setupFilesAfterEnv: [ '<rootDir>/tests/client/setup-test-framework.js' ],
	verbose: false,
	globals: {
		google: {},
	},
};
