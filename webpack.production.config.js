var webpack = require( 'webpack' ),
	webpackBaseConfig = require( './webpack.config.js' ),
	plugins = webpackBaseConfig.plugins || [],
	output = webpackBaseConfig.output || {};

delete output.publicPath;

var	productionConfig = Object.assign( {}, webpackBaseConfig, {
		output,
		plugins
	} );

productionConfig.plugins.push( new webpack.optimize.UglifyJsPlugin() );
productionConfig.plugins.push( new webpack.optimize.DedupePlugin() );

module.exports = productionConfig;