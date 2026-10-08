import { test as setup, type Browser } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { ADMIN_STORAGE_STATE_PATH, STORAGE_DIR_PATH } from '../utils/paths';
import { loginAsAdminWithQitFallback } from '../utils/qit';
import { hasAuthenticatedAdminSession } from '../utils/admin-session';

const isAuthenticatedSession = async ( browser: Browser, baseURL: string ) => {
	// newContext() throws on a corrupt/truncated storage-state file (e.g. the
	// process was killed mid-write); treat any failure to open or probe the
	// stored session as "not authenticated" so the caller deletes it and
	// re-logs-in, instead of every run failing with an opaque JSON-parse error
	// until someone discovers RESET_E2E_SESSION=1.
	let context;
	try {
		context = await browser.newContext( {
			baseURL,
			storageState: ADMIN_STORAGE_STATE_PATH,
		} );
	} catch {
		return false;
	}

	const page = await context.newPage();

	try {
		await page.goto( 'wp-admin/' );
		// Await before the finally closes the context, otherwise the in-flight
		// page.evaluate is aborted and reported as an invalid session.
		return await hasAuthenticatedAdminSession( page );
	} catch {
		return false;
	} finally {
		await context.close();
	}
};

setup.beforeAll( async () => {
	if (
		process.env.RESET_E2E_SESSION === '1' &&
		fs.existsSync( STORAGE_DIR_PATH )
	) {
		fs.rmSync( STORAGE_DIR_PATH, { recursive: true, force: true } );
	}
} );

setup( 'authenticate admin', async ( { browser, baseURL } ) => {
	if ( ! baseURL ) {
		throw new Error(
			'Playwright baseURL is not configured. Set BASE_URL and retry.'
		);
	}

	if (
		fs.existsSync( ADMIN_STORAGE_STATE_PATH ) &&
		process.env.RESET_E2E_SESSION !== '1'
	) {
		const hasValidSession = await isAuthenticatedSession(
			browser,
			baseURL
		);
		if ( hasValidSession ) {
			return;
		}

		fs.rmSync( ADMIN_STORAGE_STATE_PATH, { force: true } );
	}

	const context = await browser.newContext( { baseURL } );
	const page = await context.newPage();

	try {
		await loginAsAdminWithQitFallback( page );

		fs.mkdirSync( path.dirname( ADMIN_STORAGE_STATE_PATH ), {
			recursive: true,
		} );
		await context.storageState( {
			path: ADMIN_STORAGE_STATE_PATH,
		} );
	} finally {
		await context.close();
	}
} );
