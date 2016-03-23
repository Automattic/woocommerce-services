var webpack = require( 'webpack' ),
	webpackBaseConfig = require( './webpack.config.js' ),
	plugins = webpackBaseConfig.plugins || [],
	productionConfig = Object.assign( {}, webpackBaseConfig, {
		plugins
	} );

productionConfig.plugins.push( new webpack.optimize.UglifyJsPlugin() );
productionConfig.plugins.push( new webpack.optimize.DedupePlugin() );

module.exports = productionConfig;