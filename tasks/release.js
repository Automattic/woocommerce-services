/* eslint-disable no-console, import/no-nodejs-modules */
/* eslint no-process-exit: 0, no-undef: 0, strict: 0 */
'use strict';
require( 'shelljs/global' );
const chalk = require( 'chalk' );
const confirm = require( 'confirm-simple' );
const archiver = require( 'archiver' );
const fs = require( 'fs' );

// some config
const releaseFolder = 'release';
const targetFolder = 'release/woocommerce-services';
const dirsToCopy = [
	'classes',
	'dist',
	'i18n',
	'images',
	'vendor',
];

confirm( chalk.cyan( 'Howdy! This script is going to create a release folder with a compiled ' +
	'zipped up plugin ready for release. This script assumes you\'ve already checked out the correct branch, ' +
	'are running the latest code, and have run tests as needed. Sound good?' ), ( ok ) => {
	if ( ! ok ) {
		console.log( 'Okay. Abandoning ship'.magenta );
		process.exit( 0 );
	}

	// Install only the non-dev Composer dependencies
	rm( '-rf', 'vendor' );
	exec( 'composer install --no-dev --optimize-autoloader' );

	// run npm dist
	rm( '-rf', 'dist' );
	exec( 'npm run dist' );

	// start with a clean release folder
	rm( '-rf', releaseFolder );
	mkdir( releaseFolder );
	mkdir( targetFolder );

	// copy the main php file and readme.txt
	cp( 'woocommerce-services.php', targetFolder );
	cp( 'readme.txt', targetFolder );

	// copy the directories to the release folder
	cp( '-Rf', dirsToCopy, targetFolder );

	const output = fs.createWriteStream( releaseFolder + '/woocommerce-services.zip' );
	const archive = archiver( 'zip' );

	output.on( 'close', () => {
		console.log( chalk.cyan( 'Finished building release. Installing Composer with dev dependencies that were removed to prepare the release...' ) );

		// Reinstall dev Composer dependencies so development can continue post-release :)
		rm( '-rf', 'vendor' );
		exec( 'composer install' );

		console.log( chalk.green( 'All done: Release is built in the ' + releaseFolder + ' folder.' ) );
	} );

	archive.on( 'error', ( err ) => {
		console.error( chalk.red( 'An error occured while creating the zip: ' + err +
			'\nYou can still probably create the zip manually from the ' + targetFolder + ' folder.' ) );
	} );

	archive.pipe( output );

	archive.directory( targetFolder, '' );

	archive.finalize();
} );
