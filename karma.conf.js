const baseWebpackConfig = require( './webpack.config' );
const path = require( 'path' );

module.exports = function( config ) {
	config.set({
		browsers: [ 'jsdom' ],
		frameworks: [ 'mocha', 'chai' ],
		files: [
			'client/**/test/*.js',
		],
		preprocessors: {
			'client/**/test/*.js': [ 'webpack', 'sourcemap' ]
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
			module: Object.assign( baseWebpackConfig.module, {
				preLoaders: [
					{
						test: /\.jsx?$/,
						loader: 'isparta-instrumenter',
						query: {
							babel: baseWebpackConfig.babelSettings,
						},
						include: path.resolve( __dirname, 'client' ),
					},
				],
			} ),
			devtool: 'eval',
		} ),
		webpackMiddleware: {
			noInfo: true
		}
	});
};
