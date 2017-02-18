var webpack = require( 'webpack' ),
	config = require( './webpack.config.js' );

delete config.output.publicPath;
delete config.devtool;

config.plugins.push( new webpack.DefinePlugin( {
	'process.env.NODE_ENV': '"production"'
} ) );

config.plugins.push( new webpack.optimize.OccurrenceOrderPlugin( false ) );

config.plugins.push( new webpack.optimize.UglifyJsPlugin( {
	compress: {
		screw_ie8: true,
		warnings: false,
		unsafe: true,
	},
	mangle: {
		screw_ie8: true,
	},
	output: {
		comments: false,
		screw_ie8: true,
	},
} ) );

config.plugins.push( new webpack.optimize.DedupePlugin() );

module.exports = config;
