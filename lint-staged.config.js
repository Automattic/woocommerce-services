module.exports = {
	'*.js': ( files ) => ( files.length ? `eslint --fix ${ files.join( ' ' ) }` : [] ),
	'*.php': ( files ) => ( files.length ? [ 'sh bin/wc-phpcbf.sh ' + files.join( ' ' ) ] : [] ),
};
