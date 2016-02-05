module.exports = {
	entry: [
		'babel-polyfill',
		'./src/main.js'
	],
	output: {
		filename: './build/bundle.js'
	},
	resolve: {
		extensions: ['', '.js']
	},
	module: {
		loaders: [{
			loader: "babel",

			query: {
				presets: ['es2015', 'react']
			},

			exclude: /node-modules/,

			test: /\.js$/

		}]
	}
};
