/* eslint-disable no-console, import/no-nodejs-modules */
const path = require( 'path' );
const fs = require( 'fs' );

const testConfigFilePath = path.resolve( __dirname, '../e2e/config/default.json' );
const testConfigFile = fs.readFileSync( testConfigFilePath );
const testConfig = JSON.parse( testConfigFile );

console.log( testConfig.url.slice( 0, -1 ) );
