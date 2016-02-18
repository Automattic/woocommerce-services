module.exports = {
	entry: [
		'babel-polyfill',
		'./src/main.js'
	],
	output: {
		filename: './build/bundle.js'
	},
	resolve: {
		extensions: [ '', '.js', '.jsx' ]
	},
	module: {
		loaders: [{
			loader: "babel",
			query: {
				presets: ['es2015', 'react']
			},
			include: [
				__dirname + '/src',
				__dirname + '/node_modules/@automattic/dops-components/client'
			],
			test: /\.jsx?$/
		}]
	}
};
