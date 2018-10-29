const path = require( 'path' );
const conf = require( 'wp-calypso/.eslintrc' );

delete conf.rules[ 'import/no-extraneous-dependencies' ];
// TODO: uncomment this when https://github.com/benmosher/eslint-plugin-import/pull/1176 is released
//conf.rules[ 'import/no-extraneous-dependencies' ][ 1 ].packageDir = [
//	path.resolve( __dirname ),
//	path.resolve( __dirname, 'node_modules', 'wp-calypso' ),
//];

module.exports = conf;
