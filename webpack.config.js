/* eslint-disable import/no-nodejs-modules */
const webpack = require( 'webpack' );
const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const autoprefixer = require( 'autoprefixer' );
const url = require( 'postcss-url' );
const customProperties = require( 'postcss-custom-properties' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const os = require( 'os' );

const isProd = 'production' === process.env.NODE_ENV;
const isI18n = 'i18n' === process.env.NODE_ENV;
const isDev = ! isProd && ! isI18n;

const browsers = 'last 2 versions, not ie_mob 10, not ie 10';

const cssLoaders = [
	MiniCssExtractPlugin.loader,
	'css-loader',
	{
		loader: 'postcss-loader',
		options: {
			plugins: () => [
				autoprefixer( { browsers } ),
				customProperties( { preserve: false } ),
				url( {
					url: ( asset ) => asset.url.startsWith( 'data:' ) ? asset.url : ( 'https://wordpress.com/' + asset.url ),
				} ),
			],
		},
	},
	{
		loader: 'sass-loader',
		options: {
			includePaths: [
				path.resolve( __dirname, 'client' ),
				path.resolve( __dirname, 'client', 'extensions' ),
				path.resolve( __dirname, 'wp-calypso', 'client' ),
				path.resolve( __dirname, 'wp-calypso', 'assets', 'stylesheets' ),
			],
		},
	},
];

module.exports = {
	bail: ! isDev,
	context: __dirname,
	mode: isDev ? 'development' : 'production',
	devtool: isDev ? 'inline-source-map' : false,
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
		devtoolModuleFilenameTemplate: 'app:///[resource-path]',
		publicPath: isDev ? 'http://localhost:8085/' : '/wp-content/plugins/woocommerce-services/dist/',
	},
	optimization: {
		minimize: ! isDev,
		minimizer: [
			new TerserPlugin( {
				cache: true,
				parallel: true,
				terserOptions: {
					ecma: 5,
					mangle: {
						reserved: isI18n ? [ 'translate' ] : [],
						safari10: true,
					}
				},
			} ),
		],
	},
	performance: { hints: false },
	devServer: {
		contentBase: false,
		overlay: {
			errors: true,
			warnings: false,
		},
		disableHostCheck: true,
		headers: {
			'Access-Control-Allow-Origin': '*'
		}
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
				parser: { amd: false },
			},
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
				include: path.resolve( __dirname, 'assets', 'stylesheets' ),
				use: cssLoaders,
			},
			{
				test: /\.scss$/,
				include: [
					path.resolve( __dirname, 'client' ),
					path.resolve( __dirname, 'wp-calypso', 'client' ),
				],
				use: cssLoaders.concat( [
					{
						loader: 'wrap-loader',
						options: {
							before: [
								"@import 'shared/utils';",
								'.wp-core-ui.wp-admin .wcc-root {',
							],
							after: '}',
						},
					},
				] ),
			},
			{
				test: /\.html$/,
				use: 'html-loader',
			},
			{
				test: /\.jsx?$/,
				enforce: 'post',
				use: [
					{
						loader: 'thread-loader',
						options: {
							workers: Math.max( 2, Math.floor( os.cpus().length / 2 ) ),
						},
					},
					{
						loader: 'babel-loader',
						options: {
							configFile: path.resolve( __dirname, 'wp-calypso', 'babel.config.js' ),
							cacheDirectory: true,
							cacheIdentifier: require( './wp-calypso/server/bundler/babel/babel-loader-cache-identifier' ),
						},
					}
				],
				include: [
					path.resolve( __dirname, 'client' ),
					path.resolve( __dirname, 'wp-calypso', 'client' ),
				],
			},
			{
				test: /\.(png|jpe?g|gif)$/i,
				use: [
				  {
					loader: 'file-loader',
				  },
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
			path.resolve( __dirname, 'client', 'extensions' ),
			path.resolve( __dirname, 'wp-calypso', 'client' ),
			path.resolve( __dirname, 'wp-calypso', 'node_modules' ),
		],
		symlinks: false,
	},
	node: {
		fs: 'empty',
	},
	plugins: [
		new webpack.ProvidePlugin( {
			'fetch': 'imports-loader?this=>global!exports-loader?global.fetch!whatwg-fetch',
		} ),
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
		new webpack.DefinePlugin( {
			'process.env.NODE_ENV': isDev ? '"development"' : '"production"',
			'typeof window': '"object"',
			global: 'window',
		} ),
		new webpack.LoaderOptionsPlugin( { minimize: ! isDev } ),
		new webpack.NormalModuleReplacementPlugin( /^path$/, 'path-browserify' ),
		new webpack.IgnorePlugin( /^props$/ ),
	],
};
