import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import { ADMIN_STORAGE_STATE_PATH } from '../utils/paths';
import { disarmStub } from '../utils/stub-api';

// Arming persists: it puts the store in Jetpack offline mode, accepts the terms
// of service and has the stub answer every TaxJar request. Without this teardown
// a developer's wp-env store - where .wp-env.json activates the stub by default -
// keeps showing fabricated tax amounts after the run, with only the admin notice
// as a signal. QIT environments are disposable, so it costs them nothing.
//
// Deliberately fail-soft. Leaving the store armed is the status quo this
// improves on, so a teardown error must not turn a green run red (or mask the
// real failure in a red one). Disarming a store that was never armed is a no-op.
export default async function globalTeardown( config: FullConfig ) {
	const baseURL = config.projects.find( ( project ) => project.name === 'e2e' )
		?.use.baseURL;

	// No stored session means auth setup never completed, so nothing was armed
	// through it - and newContext() would throw on the missing file.
	if ( ! baseURL || ! fs.existsSync( ADMIN_STORAGE_STATE_PATH ) ) {
		return;
	}

	const browser = await chromium.launch();

	try {
		const context = await browser.newContext( {
			baseURL,
			storageState: ADMIN_STORAGE_STATE_PATH,
		} );

		await disarmStub( await context.newPage() );
		await context.close();
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn(
			'Could not disarm the WooCommerce Tax E2E API stub. The store may still ' +
				'show stubbed tax amounts; re-run the suite or deactivate the ' +
				'"WooCommerce Tax E2E API Stub" plugin. Underlying error:',
			error
		);
	} finally {
		await browser.close();
	}
}
