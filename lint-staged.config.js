module.exports = {
	'*.js': [
		'npm run eslint:fix',
	],
	'*.php': [ 'php -d display_errors=1 -l', 'composer run-script phpcs' ],
};
