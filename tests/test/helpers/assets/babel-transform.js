// A super simple jest transformer so we can set a custom path for babel.config.js.

const { createTransformer } = require( 'babel-jest' );
const config = require( '../../../../babel.config' );

module.exports = createTransformer( config );
