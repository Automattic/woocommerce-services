/** @format */

module.exports = {
	moduleNameMapper: {
		'^config$': '<rootDir>/wp-calypso/server/config/index.js',
		"^wcs-client/(.*)$": "<rootDir>/client/$1"
	},
	transform: {
		'^.+\\.jsx?$': '<rootDir>/tests/test/helpers/assets/babel-transform.js',
		'\\.(gif|jpg|jpeg|png|svg|scss|sass|css)$': '<rootDir>/tests/test/helpers/assets/transform.js',
	},
	modulePaths: [
		'<rootDir>/tests/',
		'<rootDir>/client/',
		'<rootDir>/client/extensions/',
		'<rootDir>/wp-calypso/client',
		'<rootDir>/wp-calypso/node_modules'
	],
	rootDir: './../../',
	roots: [ '<rootDir>/client/' ],
	testEnvironment: 'jsdom',
	transformIgnorePatterns: [
		'node_modules[\\/\\\\](?!flag-icon-css|redux-form|simple-html-tokenizer|draft-js)',
	],
	testMatch: [ '<rootDir>/client/**/test/*.js?(x)', '!**/.eslintrc.*' ],
	testURL: 'https://example.com',
	setupFiles: [ 'regenerator-runtime/runtime' ], // some NPM-published packages depend on the global
	setupFilesAfterEnv: [ '<rootDir>/tests/client/setup-test-framework.js' ],
	verbose: false,
	globals: {
		google: {},
	},
};
