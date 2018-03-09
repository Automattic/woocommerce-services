/* eslint-disable no-console, import/no-nodejs-modules */
const fs = require( 'fs' ),
	globby = require( 'globby' ),
	path = require( 'path' ),
	po2json = require( 'po2json' );

const poFilePaths = globby.sync( [ 'i18n/languages/*.po' ] );

if ( ! poFilePaths.length ) {
	console.log( 'no .po files found' );
}

poFilePaths.forEach( ( inputPath ) => {
	const file = fs.readFileSync( inputPath );
	const json = po2json.parse( file, { stringify: true } );

	const outputFilename = path.basename( inputPath, '.po' ) + '.json';
	const outputPath = path.join( 'i18n/json', outputFilename );

	fs.writeFile( outputPath, json, ( error ) => {
		if ( error ) {
			console.log( error );
		} else {
			console.log( 'saved ' + outputFilename );
		}
	} );
} );
