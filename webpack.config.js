const webpack = require( 'webpack' );
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const autoprefixer = require( 'autoprefixer' );

const isProd = 'production' === process.env.NODE_ENV;

const browsers = 'last 2 versions, not ie_mob 10, not ie 10';

const babelSettings = {
	presets: [
		[ 'env', {
			useBuiltIns: true,
			targets: { browsers },
			// modules: false, // add-module-exports breaks with WebPack 2 and modules: false
		} ],
		'stage-1',
		'react'
	],
	plugins: [
		'add-module-exports',
		'lodash',
	],
	babelrc: false,
};

const config = {
	cache: true,
	entry: {
		'woocommerce-services': [ './client/main.js' ],
		'woocommerce-services-banner': [ './assets/stylesheets/banner.scss' ],
		'woocommerce-services-admin-pointers': [ './client/admin-pointers.js' ],
	},
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: '[name].js',
	},
	externals: {
		'jquery': 'jQuery',
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				enforce: 'pre',
				use: 'eslint-loader',
				include: path.resolve( __dirname, 'client' ),
			},
			{
				test: /\.scss$/,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: [
						'css-loader',
						{
							loader: 'postcss-loader',
							options: {
								plugins: () => [ autoprefixer( { browsers } ) ],
							},
						},
						{
							loader: 'sass-loader',
							options: {
								includePaths: [
									path.resolve( __dirname, 'client' ),
									path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
									path.resolve( __dirname, 'node_modules', 'wp-calypso', 'assets', 'stylesheets' ),
								],
							},
						},
					],
				} ),
			},
			{
				test: /\.html$/,
				use: 'html-loader',
			},
			{
				test: /\.svg$/,
				use: 'svg-url-loader',
			},
			{
				test: /\.png$/,
				use: {
					loader: 'url-loader',
					options: { limit: 10000 },
				},
			},
			{
				test: /\.jsx?$/,
				enforce: 'post',
				use: {
					loader: 'babel-loader',
					options: babelSettings,
				},
				include: [
					path.resolve( __dirname, 'client' ),
					path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
				],
			},
		],
		// google-libphonenumber is pre-compiled, suppress the warning for that module
		noParse: /.*google-libphonenumber.*/,
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [
			path.resolve( __dirname, 'client' ),
			path.resolve( __dirname, 'node_modules' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'node_modules' ),
		],
	},
	plugins: [
		new webpack.ProvidePlugin( {
			'fetch': 'imports-loader?this=>global!exports-loader?global.fetch!whatwg-fetch',
		} ),
		new ExtractTextPlugin( {
			filename: '[name].css',
			disable: ! isProd,
		} ),
	],
};

if ( isProd ) {

	babelSettings.plugins.push( 'transform-react-remove-prop-types' );

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

} else {

	config.output.publicPath = 'http://localhost:8085/';

	config.devtool = '#inline-source-map';

}

module.exports = config;
