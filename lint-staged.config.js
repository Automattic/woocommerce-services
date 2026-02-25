module.exports = {
	'*.js': ( files ) => {
		const toFix = files.filter( ( f ) => ! f.includes( '/wp-calypso/' ) );
		return toFix.length ? `eslint --fix ${ toFix.join( ' ' ) }` : [];
	},
	'*.php': ( files ) => {
		const toFix = files.filter( ( f ) => ! f.includes( '/wp-calypso/' ) );
		return toFix.length ? [ 'sh bin/wc-phpcbf.sh ' + toFix.join( ' ' ) ] : [];
	},
};
