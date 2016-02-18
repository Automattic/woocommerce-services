var webpack = require("webpack"),
	fs = require('fs'),
	path = require('path'),
	ExtractTextPlugin = require("extract-text-webpack-plugin");

var styleDefaults = 'css?sourceMap!autoprefixer!sass?sourceMap!custom-colors';

var cssLoader = ExtractTextPlugin.extract('css?sourceMap!autoprefixer!');

var scssLoader = ExtractTextPlugin.extract(styleDefaults);

// build the plugin list, optionally excluding hot update plugins if we're building for production
var plugins = [
	new ExtractTextPlugin("bundle.css")
].filter( function(plugin) { return plugin !== false; } );

module.exports = {
	progress: true,
	entry: [
		'./src/main.js'
	],
	output: {
		publicPath: '/assets/',
		path: path.resolve(__dirname, "build"),
		filename: 'bundle.js',
		chunkFilename: "[id].js"
	},
	resolve: {
		extensions: ["", ".js", ".jsx"],
		alias: {
			"react": path.join(__dirname, "/node_modules/react")
		},
		root: [ path.resolve( __dirname, 'src' ), fs.realpathSync( path.join(__dirname, 'node_modules/@automattic/dops-components/client') ) ]
	},
	resolveLoader: {
		root: path.join( __dirname, "node_modules" ),
		alias: {
			'custom-colors': '@automattic/custom-colors-loader'
		}
	},
	node: {
		fs: "empty"
	},
	stats: { colors: true, reasons: true },
	module: {
		loaders: [
			{
				test: /\.jsx?$/,
				loader: require.resolve('babel-loader'),
				query: {
					presets: [ 'es2015', 'react', 'stage-1' ]
				},

				// include both typical npm-linked locations and default module locations to handle both cases
				include: [
					path.join(__dirname, 'src'),
					fs.realpathSync( path.join(__dirname, 'node_modules/@automattic/dops-components/client') ),
					path.join(__dirname, 'node_modules/@automattic/dops-components/client')
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