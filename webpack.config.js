const webpack = require( 'webpack' );
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );

const babelSettings = {
	presets: [
		'es2015',
		'stage-1',
		'react'
	],
	plugins: [
		"add-module-exports",
	],
	babelrc: false,
};

module.exports = {
	babelSettings,
	cache: true,
	entry: {
		'woocommerce-connect-client': [ 'babel-polyfill', './client/main.js' ],
	},
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: '[name].js',
		publicPath: 'http://localhost:8085/',
	},
	devtool: '#eval',
	module: {
		loaders: [
			{
				test: /\.json$/,
				loader: 'json-loader'
			},
			{
				test: /\.scss$/,
				loader: ExtractTextPlugin.extract( 'style', 'css!sass' )
			},
			{
				test: /\.html$/,
				loader: 'html-loader'
			},
			{
				test: /\.jsx?$/,
				loaders: [
					'babel?' + JSON.stringify( babelSettings ),
					'eslint'
				],
				include: /(client|wp-calypso)/,
				exclude: /(wp-calypso\/node_modules)/,
			}
		]
	},
	sassLoader: {
		includePaths: [
			path.resolve( __dirname, './client' ),
			path.resolve( __dirname, './node_modules/wp-calypso/client' ),
			path.resolve( __dirname, './node_modules/wp-calypso/assets/stylesheets' ),
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
	plugins: [
		new webpack.ProvidePlugin( {
			'fetch': 'imports?this=>global!exports?global.fetch!whatwg-fetch'
		} ),
		new ExtractTextPlugin( '[name].css' ),
	],
};
