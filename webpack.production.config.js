const webpack = require( 'webpack' );
const devConfig = require( './webpack.config.js' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );

devConfig.babelSettings.plugins.push( 'transform-react-remove-prop-types' );

const config = devConfig.getConfig();

delete config.output.publicPath;
delete config.devtool;

config.plugins.push( new webpack.LoaderOptionsPlugin( { minimize: true } ) );

config.plugins.push( new webpack.DefinePlugin( {
	'process.env.NODE_ENV': '"production"'
} ) );

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

config.plugins.push( new ExtractTextPlugin( '[name].css' ) );

module.exports = config;
