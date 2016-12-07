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
		preLoaders: [
			{
				test: /\.jsx?$/,
				loader: 'eslint',
				include: path.resolve( __dirname, 'client' ),
			},
		],
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
				test: /\.svg$/,
				loader: 'svg-url-loader',
				include: /(client)/,
			},
			{
				test: /\.png$/,
				loader: 'url-loader?limit=10000',
			}
		],
		postLoaders: [
			{
				test: /\.jsx?$/,
				loader: 'babel?' + JSON.stringify( babelSettings ),
				include: [
					path.resolve( __dirname, 'client' ),
					path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
				],
			},
		],
		// google-libphonenumber is pre-compiled, suppress the warning for that module
		noParse: /.*google-libphonenumber.*/,
	},
	sassLoader: {
		includePaths: [
			path.resolve( __dirname, 'client' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'assets', 'stylesheets' ),
		]
	},
	resolve: {
		extensions: [ '', '.json', '.js', '.jsx' ],
		root: [
			path.resolve( __dirname, 'client' ),
			path.resolve( __dirname, 'node_modules' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
		],
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
