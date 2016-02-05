module.exports = {
	entry: './assets/js/src/main.js',
	output: {
		filename: './assets/js/bundle.js'
	},
	resolve: {
		extensions: ['', '.js', '.jsx']
	},
	module: {
		loaders: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loaders: ['babel-loader']
			},
			{
				test: /\.jsx$/,
				loaders: ['babel-loader']
			}
		]
	}
};
