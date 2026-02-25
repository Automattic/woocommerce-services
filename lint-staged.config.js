module.exports = {
	'*.js': () => 'npm run eslint:fix',
	'*.php': ( files ) => {
		const toFix = files.filter( ( f ) => ! f.includes( '/wp-calypso/' ) );
		return toFix.length ? [ 'sh bin/wc-phpcbf.sh ' + toFix.join( ' ' ) ] : [];
	},
};
