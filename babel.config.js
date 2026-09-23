/** @format */

/**
 * Babel configuration for the webpack build and the jest suite.
 *
 * This used to be `wp-calypso/babel.config.js`, which extended
 * `@automattic/calypso-build/babel.config.js` from the submodule's own install. The two
 * files are flattened here into the options they actually resolved to, so neither the
 * build nor the tests need the submodule. Every preset and plugin below is already a
 * direct dependency of this package; the three Calypso-specific transforms are vendored
 * under `tasks/babel/`.
 *
 * Calypso's `env.build_pot` block is deliberately not carried over: it existed to drive
 * `@automattic/babel-plugin-i18n-calypso`, and nothing here ever set `BABEL_ENV=build_pot`.
 * Translation strings are extracted from the built bundles instead - see `tasks/i18n.js`.
 */

const path = require( 'path' );

// Calypso set this to build a server bundle; we only ever build for the browser, but keep
// the switch so the intent of `modules` and the browser-only transforms stays legible.
const isBrowser = process.env.BROWSERSLIST_ENV !== 'server';

module.exports = {
	presets: [
		[
			'@babel/preset-env',
			{
				modules: isBrowser ? false : 'commonjs',
				useBuiltIns: 'entry',
				corejs: 2,
				// Exclude transforms that make all code slower.
				// See https://github.com/facebook/create-react-app/pull/5278
				exclude: [ 'transform-typeof-symbol' ],
			},
		],
		'@babel/preset-react',
	],
	plugins: [
		'@babel/plugin-proposal-class-properties',
		'@babel/plugin-proposal-export-default-from',
		'@babel/plugin-proposal-export-namespace-from',
		'@babel/plugin-syntax-dynamic-import',
		[
			'@babel/plugin-transform-runtime',
			{
				corejs: false, // we polyfill, so core-js helpers are not needed
				helpers: true,
				regenerator: false,
				useESModules: false,
			},
		],
		[
			path.join( __dirname, 'tasks', 'babel', 'transform-wpcalypso-async.js' ),
			// Calypso read this from its own `code-splitting` feature flag, which was on for
			// browser builds; `client/main.js` relies on the async chunk splitting it emits.
			{ async: isBrowser },
		],
		...( isBrowser ? [ path.join( __dirname, 'tasks', 'babel', 'inline-imports.js' ) ] : [] ),
	],
	env: {
		test: {
			presets: [ [ '@babel/preset-env', { targets: { node: 'current' } } ] ],
			plugins: [
				'babel-plugin-add-module-exports',
				'babel-plugin-dynamic-import-node',
				path.join( __dirname, 'tasks', 'babel', 'lodash-es.js' ),
			],
		},
	},
};
