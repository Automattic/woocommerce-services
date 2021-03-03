const config = {
	env: {
		test: {
			presets: [ [ '@babel/preset-env', { targets: { node: 'current' } } ] ],
		},
		production: {
			presets: [ '@automattic/calypso-build/babel/default' ],
		},
	  }
};

module.exports = config;
