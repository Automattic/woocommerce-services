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
		warnings: false,
		unsafe: true,
	},
} ) );

config.plugins.push( new webpack.optimize.DedupePlugin() );

module.exports = config;
