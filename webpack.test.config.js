var webpack = require( 'webpack' ),
	path = require( 'path' );

module.exports = {
	cache: true,
	entry: {
		'all_tests': './tests/js/test_settings.js'
	},
	output: {
		path: path.join( __dirname, 'tests', 'js' ),
		filename: '[name].js'
	},
	//devtool: '#eval',
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
				test: /\.jsx?$/,
				loader: 'babel-loader'
			}
		],
		noParse: [
			/node_modules\/(?!wp\-calypso).*/
		]
	},
	resolve: {
		alias: {
			'react': path.join( __dirname, 'node_modules', 'react' ),
			'react-dom': path.join( __dirname, 'node_modules', 'react-dom' )
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
	target: 'node'
};