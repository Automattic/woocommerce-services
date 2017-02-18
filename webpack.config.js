const webpack = require( 'webpack' );
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const autoprefixer = require( 'autoprefixer' );

const browsers = 'last 2 versions, not ie_mob 10, not ie 10';

const babelSettings = {
	presets: [
		[ 'env', { useBuiltIns: true, targets: { browsers } } ],
		'stage-1',
		'react'
	],
	plugins: [
		'add-module-exports',
		'lodash',
	],
	babelrc: false,
};

const getConfig = () => ( {
	babelSettings,
	cache: true,
	entry: {
		'woocommerce-services': [ './client/main.js' ],
		'woocommerce-services-banner': [ './assets/stylesheets/banner.scss' ],
		'woocommerce-services-admin-pointers': [ './client/admin-pointers.js' ],
	},
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: '[name].js',
		publicPath: 'http://localhost:8085/',
	},
	externals: {
		'jquery': 'jQuery',
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
	resolveLoader: { root: path.resolve( __dirname, 'node_modules' ) },
	plugins: [
		new webpack.ProvidePlugin( {
			'fetch': 'imports?this=>global!exports?global.fetch!whatwg-fetch'
		} ),
		new ExtractTextPlugin( '[name].css' ),
	],
	postcss: function () {
		return [ autoprefixer( { browsers } ) ];
	},
} );

module.exports = getConfig();
module.exports.getConfig = getConfig;
module.exports.babelSettings = babelSettings;
