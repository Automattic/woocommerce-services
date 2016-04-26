var webpack = require( 'webpack' ),
	path = require( 'path' ),
	nodeExternals = require( 'webpack-node-externals' );

module.exports = {
	cache: true,
	module: {
		loaders: [
			{
				test: /\.json$/,
				loader: 'json-loader'
			},
			{
				test: /\.html$/,
				loader: 'html-loader'
			},
			{
				test: /\.scss$/,
				loader: 'noop-loader',
			},
			{
				test: /\.jsx?$/,
				loader: 'babel-loader',
				query: {
					cacheDirectory: true,
					presets: [
						'es2015',
						'stage-1',
						'react'
					],
					plugins: [
						"add-module-exports",
					],
					babelrc: false,
				}
			}
		]
	},
	resolve: {
		alias: {
			'react': path.join( __dirname, 'node_modules', 'react' ),
			'react-dom': path.join( __dirname, 'node_modules', 'react-dom' ),
			'redux': path.join( __dirname, 'node_modules', 'redux' ),
			'lib/mixins/i18n': path.join( __dirname, 'client', 'lib', 'mixins', 'i18n' )
		},
		extensions: [ '', '.json', '.js', '.jsx' ],
		root: [
			path.join( __dirname, 'client' ),
			path.join( __dirname, 'node_modules' ),
			path.join( __dirname, 'node_modules', 'wp-calypso', 'client' )
		],
		fallback: [
			path.join( __dirname, 'node_modules', 'wp-calypso', 'node_modules' )
		]
	},
	resolveLoader: {
		modulesDirectories: [ __dirname + '/node_modules' ]
	},
	target: 'node',
	externals: [
		nodeExternals( {
			whitelist: [ 'wp-calypso' ]
		} )
	]
};