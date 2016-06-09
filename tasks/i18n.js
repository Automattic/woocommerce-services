#!/usr/bin/env node

/**
 * Module dependencies/
 */
var fs = require( 'fs' ),
	Xgettext = require( 'xgettext-js' ),
	preProcessXGettextJSMatch = require( '../node_modules/wp-calypso/server/i18n/preprocess-xgettextjs-match.js' ),
	uniq = require( 'lodash/uniq' ),
	parser;

// parser object that buids a WordPress php string for every
// occurence of `translate()` in a file
parser = new Xgettext( {
	keywords: {
		translate: function( match ) {
			var finalProps = preProcessXGettextJSMatch( match );

			if ( ! finalProps ) {
				return; // invalid input, skip this match
			}
			return buildWordPressString( finalProps );
		},
	}
} );

/**
 * Generate each line of equivalent php from a matching `translate()`
 * request found in the client code
 * @param  {object} properties - properties describing translation request
 * @return {string}            the equivalent php code for each translation request
 */
function buildWordPressString( properties ) {
	var wpFunc = properties.context ? '_x' : '__',
		response = [],
		stringFromFunc = {
			__: '__( ' + properties.single + ' )',
			_x: '_x( ' + [ properties.single, properties.context ].join( ', ' ) + ' )',
		};

	// translations with comments get a preceding comment in the php code
	if ( properties.comment ) {
		// replace */ with *\/ to prevent translators from accidentally running arbitrary code
		response.push( '\n\t/* translators: ' + properties.comment.replace( /\*\//g, '*\\/' ) + ' */' );
	}

	response.push( '\n\t' + properties.single + ' => ' + stringFromFunc[ wpFunc ] + ',' );

	return response.join( '' );
}

/**
 * Takes read file and generates a string representation of a file with
 * equivalent WordPress-style translate functions. Also prepends with some
 * necessary time and number translations.
 *
 * @param  {array} data        - the input file as read in by fs.readFile()
 * @param  {string} arrayName  - name of the array in the php resulting php file
 * @return {string}            - string representation of the final php file
 */
function buildPhpOutput( data, arrayName ) {
	// find matching instances of `translate()` and generate corresponding php output
	var matches = parser.getMatches( data );

	matches = uniq( matches.map( function( match ) {
		return match.string;
	} ) );

	// prepend the matches array with this content to open the php file
	matches.unshift( [
		'<?php',
		'\n/* THIS IS A GENERATED FILE. DO NOT EDIT DIRECTLY */',
		'\n$' + arrayName + ' = array('
	].join( '' ) );

	// append the generated file comment to the bottom of the array as well
	matches.push( '\n);\n/* THIS IS THE END OF THE GENERATED FILE */\n' );

	return matches.join( '' );
}

/**
 * Reads the inputFile and generates a php outputFile with equivalent php translation functions.

 * @param  {string}   outputFile - location to save the resulting output file
 * @param  {string}   arrayName  - name of array to use inside php file
 * @param  {string}   inputFile  - location of the javascript file to parse
 */
function readFile( outputFile, arrayName, inputFile ) {
	console.log( 'Reading inputFile: ' + inputFile );
	fs.readFile( inputFile, 'utf8', function( err, data ) {
		if ( err ) {
			console.log( 'i18n: Error reading ' + inputFile );
			return;
		}
		fs.writeFile( outputFile, buildPhpOutput( data, arrayName ), 'utf8', function( error ) {
			if ( error ) {
				console.log( error );
			} else {
				console.log( 'get-i18n completed' );
			}
		} );
	} );
}

readFile( 'i18n/strings.php', 'i18nStrings', 'dist/woocommerce-connect-client.js' );
