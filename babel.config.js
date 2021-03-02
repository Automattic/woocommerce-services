const config = {
	presets: [
		[
			'@babel/preset-env',
			{ targets: { node: 'current' } },
			'@automattic/calypso-build/babel/default',
		],
	],
};

module.exports = config;
