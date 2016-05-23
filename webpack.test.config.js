const webpack = require( 'webpack' );
const path = require( 'path' );
const nodeExternals = require( 'webpack-node-externals' );
const webpackBaseConfig = require( './webpack.config' );

const webpackTestConfig = Object.assign( {}, webpackBaseConfig, {
	externals: nodeExternals( {
		whitelist: [ 'wp-calypso' ],
	} ),
} );

module.exports = webpackTestConfig;
