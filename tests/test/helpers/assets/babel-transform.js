// A super simple jest transformer so we can set a custom path for babel.config.js.

const { createTransformer } = require( 'babel-jest' );
const { map } = require( 'lodash' );
const config = require( '../../../../wp-calypso/babel.config' );

// Remap a couple paths for new root location.
config.plugins = map( config.plugins, ( plugin ) => {
	return './inline-imports.js' === plugin ? './wp-calypso/inline-imports.js' : plugin;
} );
config.env.test.plugins = map( config.env.test.plugins, ( plugin ) => {
	return './server/bundler/babel/babel-lodash-es' === plugin ? './wp-calypso/server/bundler/babel/babel-lodash-es' : plugin;
} );

module.exports = createTransformer( config );
