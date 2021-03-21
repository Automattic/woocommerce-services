module.exports = {
	'*.js': [
		'npm run eslint:fix',
	],
	'*.php': [
		'bin/wc-phpcbf',
	],
};
