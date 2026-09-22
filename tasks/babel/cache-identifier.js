/* eslint-disable import/no-nodejs-modules */
/** @format */

/**
 * Cache identifier for babel-loader.
 *
 * babel-loader's default identifier covers its own version, @babel/core's version and the
 * env, but not our config or the transforms we vendor - so a change to either would
 * otherwise be served from a stale cache. Replaces the equivalent helper that used to live
 * in the wp-calypso submodule.
 *
 * @see https://github.com/babel/babel-loader/blob/501d60d/src/index.js#L85-L92
 */

const fs = require( 'fs' );
const path = require( 'path' );

const moduleVersion = ( id ) => require( `${ id }/package.json` ).version;

const readConfig = ( file ) => fs.readFileSync( path.resolve( __dirname, '..', '..', file ), 'utf8' );

module.exports = JSON.stringify( {
	'babel-loader': moduleVersion( 'babel-loader' ),
	'babel-core': moduleVersion( '@babel/core' ),
	'babel-config': readConfig( 'babel.config.js' ),
	'transform-wpcalypso-async': fs.readFileSync(
		path.join( __dirname, 'transform-wpcalypso-async.js' ),
		'utf8'
	),
	'inline-imports': fs.readFileSync( path.join( __dirname, 'inline-imports.js' ), 'utf8' ),
	env: process.env.BABEL_ENV || process.env.NODE_ENV,
} );
