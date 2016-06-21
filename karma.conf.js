const baseWebpackConfig = require( './webpack.config' );

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
		reporters: [ 'mocha', 'coverage' ],
		coverageReporter: {
			dir: 'coverage/',
			includeAllSources: true,
			reporters: [
				{ type: 'html', subdir: 'report-html' },
				{ type: 'text-summary' },
			]
		},
		webpack: {
			module: {
				preLoaders: [
					{
						test: /\.jsx?$/,
						loader: 'isparta-instrumenter',
						query: {
							babel: baseWebpackConfig.babelSettings,
						},
						include: /client/i,
						exclude: /(test|node_modules)/i,
					},
				],
				loaders: [
					{
						test: /\.scss$/,
						loader: 'null'
					},
					{
						test: /\.json$/,
						loader: 'json'
					},
					{
						test: /\.jsx?$/,
						loader: 'babel',
						query: baseWebpackConfig.babelSettings,
						include: /(client|node_modules\/wp-calypso\/client)/i,
						exclude: /(node_modules\/(?!wp-calypso\/client)|wp-calypso\/node_modules)/i,
					},
				]
			},
			devtool: 'eval',
			resolve: baseWebpackConfig.resolve,
			resolveLoader: baseWebpackConfig.resolveLoader,
		},
		webpackMiddleware: {
			noInfo: true
		}
	});
};
