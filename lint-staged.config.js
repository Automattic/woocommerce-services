module.exports = {
	'*.js': [
		'npm run eslint:fix',
	],
	'*.php': [
		'sh bin/wc-phpcbf.sh',
	],
};
