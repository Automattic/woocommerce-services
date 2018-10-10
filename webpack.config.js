const webpack = require( 'webpack' );
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const autoprefixer = require( 'autoprefixer' );

const isProd = 'production' === process.env.NODE_ENV;
const isI18n = 'i18n' === process.env.NODE_ENV;

const browsers = 'last 2 versions, not ie_mob 10, not ie 10';

process.noDeprecation = true; // see https://github.com/webpack/loader-utils/issues/56

const babelSettings = {
	presets: [
		[ 'env', {
			useBuiltIns: true,
			targets: { browsers },
			loose: true,
			// modules: false, // add-module-exports breaks with WebPack 2 and modules: false
		} ],
		'stage-1',
		'react'
	],
	plugins: [
		'add-module-exports',
		'lodash',
		[
			'transform-imports',
			{
				'state/selectors': {
					transform: 'state/selectors/${member}',
					kebabCase: true,
				},
			},
		],
	],
	babelrc: false,
};

const config = {
	cache: true,
	entry: {
		'woocommerce-services': [ './client/main.js' ],
		'woocommerce-services-banner': [ './client/banner.js' ],
		'woocommerce-services-admin-pointers': [ './client/admin-pointers.js' ],
		'woocommerce-services-new-order-taxjar': [ './client/new-order-taxjar.js' ],
	},
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: '[name].js',
	},
	externals: {
		'jquery': 'jQuery',
		'cheerio': 'window',
		'react/addons': true,
		'react/lib/ExecutionEnvironment': true,
		'react/lib/ReactContext': true,
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				enforce: 'pre',
				use: {
					loader: 'eslint-loader',
					options: {
						emitWarning: true,
					},
				},
				include: path.resolve( __dirname, 'client' ),
			},
			{
				test: /\.scss$/,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: [
						{
							loader: 'css-loader',
							options: {
								root: 'https://wordpress.com',
							},
						},
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
									path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client', 'extensions' ),
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
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [
			path.resolve( __dirname, 'client' ),
			path.resolve( __dirname, 'client', 'calypso-stubs' ),
			path.resolve( __dirname, 'client', 'calypso-stubs', 'extensions' ),
			path.resolve( __dirname, 'node_modules' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'client', 'extensions' ),
			path.resolve( __dirname, 'node_modules', 'wp-calypso', 'node_modules' ),
		],
		symlinks: false,
	},
	node: {
		fs: 'empty'
	},
	plugins: [
		new webpack.ProvidePlugin( {
			'fetch': 'imports-loader?this=>global!exports-loader?global.fetch!whatwg-fetch',
		} ),
		new ExtractTextPlugin( {
			filename: '[name].css',
		} ),
	],
};

if ( isProd || isI18n ) {
	config.plugins.push( new webpack.LoaderOptionsPlugin( { minimize: true } ) );

	config.plugins.push( new webpack.DefinePlugin( {
		'process.env.NODE_ENV': '"production"',
		'typeof window': '"object"',
	} ) );

	config.plugins.push( new webpack.optimize.UglifyJsPlugin( {
		compress: {
			screw_ie8: true,
			warnings: false,
			unsafe: true,
		},
		mangle: {
			screw_ie8: true,
			except: isProd ? [] : [ 'translate' ]
		},
		output: {
			comments: false,
			screw_ie8: true,
		},
	} ) );

} else {

	config.output.publicPath = 'http://localhost:8085/';

	config.devtool = '#inline-source-map';

	config.devServer = {
		overlay: {
			errors: true,
			warnings: false,
		},
	};

}

module.exports = config;
