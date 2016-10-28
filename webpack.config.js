const webpack = require( 'webpack' );
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const autoprefixer = require( 'autoprefixer' );

const babelSettings = {
	presets: [
		'es2015',
		'stage-1',
		'react'
	],
	plugins: [
		'add-module-exports',
		'lodash',
	],
	babelrc: false,
};

module.exports = {
	babelSettings,
	cache: true,
	entry: {
		'woocommerce-connect-client': [ './client/main.js' ],
		'woocommerce-connect-client-banner': [ './assets/stylesheets/banner.scss' ],
	},
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: '[name].js',
		publicPath: 'http://localhost:8085/',
	},
	devtool: '#inline-source-map',
	module: {
		loaders: [
			{
				test: /\.json$/,
				loader: 'json-loader'
			},
			{
				test: /\.scss$/,
				loader: ExtractTextPlugin.extract( 'style', 'css!postcss!sass' )
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
			},
			{
				test: /\.svg$/,
				loader: 'svg-url-loader',
				include: /(client)/,
			},
			{
				test: /\.png$/,
				loader: 'url-loader?limit=10000',
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
	postcss: function () {
		return [ autoprefixer ];
	},
};
