var webpack = require("webpack"),
	fs = require('fs'),
	path = require('path'),
	ExtractTextPlugin = require("extract-text-webpack-plugin"),
	WebpackNotifierPlugin = require('webpack-notifier');

var IS_HOT_UPDATE = (process.env.NODE_ENV !== 'production');

var styleDefaults = 'css?sourceMap!autoprefixer!sass?sourceMap!custom-colors';

var cssLoader = IS_HOT_UPDATE ?
	'style!css?sourceMap!autoprefixer!' :
	ExtractTextPlugin.extract('css?sourceMap!autoprefixer!');

var scssLoader = IS_HOT_UPDATE ?
'style!'+styleDefaults :
	ExtractTextPlugin.extract(styleDefaults);

var jsLoader = IS_HOT_UPDATE ?
	[require.resolve('react-hot-loader'), require.resolve('babel-loader'), require.resolve("eslint-loader")] :
	[require.resolve('babel-loader'), require.resolve("eslint-loader")];

// build the plugin list, optionally excluding hot update plugins if we're building for production
var plugins = [
	new ExtractTextPlugin("[name].css"),
	IS_HOT_UPDATE ? new webpack.optimize.OccurenceOrderPlugin() : false,
	IS_HOT_UPDATE ? new webpack.HotModuleReplacementPlugin() : false,
	IS_HOT_UPDATE ? new webpack.NoErrorsPlugin() : false,
	IS_HOT_UPDATE ? new WebpackNotifierPlugin() : false,
	new webpack.DefinePlugin({
		"process.env": {
			// This has effect on the react lib size
			"NODE_ENV": JSON.stringify(process.env.NODE_ENV) // TODO switch depending on actual environment
		}
	})
].filter( function(plugin) { return plugin !== false; } );

module.exports = {
	progress: true,
	entry: {
		"woocommerce-connect-client": "./client/main.js"
	},
	output: {
		publicPath: '/assets/',
		path: path.resolve(__dirname, "dist"),
		filename: "[name].js",
		chunkFilename: "[id].js"
	},
	resolve: {
		extensions: ["", ".js", ".jsx"],
		alias: {
			"react": path.join(__dirname, "/node_modules/react")
		},
		root: [ path.resolve( __dirname, 'client' ), fs.realpathSync( path.join( __dirname, 'node_modules/wp-calypso/client' ) ) ]
	},
	node: {
		fs: "empty"
	},
	stats: { colors: true, reasons: true },
	module: {
		loaders: [
			{
				test: /\.jsx?$/,
				loaders: jsLoader,

				// include both typical npm-linked locations and default module locations to handle both cases
				include: [
					path.join(__dirname, 'test'),
					path.join(__dirname, 'client'),
					fs.realpathSync( path.join(__dirname, 'node_modules/wp-calypso/client') ),
					path.join(__dirname, 'node_modules/wp-calypso/client')
				]
			},
			{
				test: /\.json$/,
				loader: require.resolve('json-loader')
			},
			{
				test: /\.css$/,
				loader: cssLoader
			},
			{
				test: /\.scss$/,
				loader: scssLoader
			},
			{
				test: /\.svg(\?v=\d+\.\d+\.\d+)?$/,
				loader: require.resolve('url-loader')+"?limit=20000&mimetype=image/svg+xml"
			}
		]
	},
	plugins: plugins
};