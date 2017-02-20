const baseWebpackConfig = require( './webpack.config' );
const path = require( 'path' );
const escapeStringRegexp = require( 'escape-string-regexp' );

const testDirMatcher = new RegExp( escapeStringRegexp( path.resolve( __dirname, 'client' ) ) + '.*' + escapeStringRegexp( path.sep + 'test' + path.sep ) );

// Run single test: npm test --test=FILE_OR_GLOB
const testFile = (process.env.npm_config_test) ? process.env.npm_config_test : 'client/test/runner.js';

module.exports = function( config ) {
	config.set({
		browsers: [ 'jsdom' ],
		frameworks: [ 'mocha', 'chai' ],
		files: [ 'client/test/mock-jquery.js', testFile ],
		preprocessors: {
			[ testFile ]: [ 'webpack', 'sourcemap' ],
		},
		client: {
			captureConsole: true
		},
		reporters: [ 'mocha', 'coverage' ],
		coverageReporter: {
			dir: 'coverage/',
			includeAllSources: true,
			reporters: [
				{ type: 'html', subdir: 'report-html' },
				{ type: 'text-summary' },
			]
		},
		webpack: Object.assign( baseWebpackConfig, {
			entry: {},
			isparta: {
				embedSource: true,
				noAutoWrap: true,
				babel: baseWebpackConfig.babelSettings,
			},
			module: Object.assign( baseWebpackConfig.module, {
				postLoaders: [
					{
						test: /\.jsx?$/,
						loader: 'babel?' + JSON.stringify( baseWebpackConfig.babelSettings ),
						include: [
							testDirMatcher,
							path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
						]
					},
					{
						test: /\.jsx?$/,
						loader: 'isparta',
						include: path.resolve( __dirname, 'client' ),
						exclude: testDirMatcher,
					},
				],
			} ),
			devtool: 'inline-source-map',
		} ),
		webpackMiddleware: {
			noInfo: true
		}
	});
};
