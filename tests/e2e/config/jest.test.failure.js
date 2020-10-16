/** @format */

/**
 * Internal dependencies
 */
import {
	sendFailedTestScreenshotToSlack,
	sendFailedTestMessageToSlack
} from "./lib/reporter/slack-reporter";

const path = require( 'path' );
const mkdirp = require( 'mkdirp' );

/**
 * Override the test case method so we can take screenshots of assertion failures.
 *
 * See: https://github.com/smooth-code/jest-puppeteer/issues/131#issuecomment-469439666
 */
let currentBlock;
const { CI } = process.env;

global.describe = ( name, func ) => {
	currentBlock = name;

	try {
		func();
	} catch ( e ) {
		throw e;
	}
};

global.it = async ( name, func ) => {
	return await test( name, async () => {
		try {
			await func();
		} catch ( error ) {
			const savePath = './tests/e2e-tests/screenshots';
			const fileName = `${ currentBlock } - ${ name }.png`;
			const testNameForTravisMessage = `${ currentBlock } - ${ name }`;
			const filePath = path.join(
				savePath,
				fileName.replace( /[^a-z0-9.-]+/gi, '-' )
			);
			mkdirp.sync( savePath );

			await page.screenshot( {
				path: filePath,
				fullPage: true,
			} );

			// If running tests in CI, send failed test details and screenshot to Slack
			if ( CI ) {
				await sendFailedTestMessageToSlack( testNameForTravisMessage );
				await sendFailedTestScreenshotToSlack( filePath );
			}

			throw error;
		}
	} );
};

global.saveScreenshot = async ( captureName ) => {
	const savePath = './tests/e2e-tests/screenshots';
	const fileName = `${ captureName }.png`;
	const filePath = path.join(
		savePath,
		fileName.replace( /[^a-z0-9.-]+/gi, '-' )
	);
	mkdirp.sync( savePath );

	await page.screenshot( {
		path: filePath,
		fullPage: true,
	} );

	// If running tests in CI, send failed test details and screenshot to Slack
	if ( CI ) {
		await sendFailedTestScreenshotToSlack( filePath );
	}
};
