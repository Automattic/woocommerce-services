import { test, expect } from '@playwright/test';
import { expectNoPhpErrors } from '../utils/php-errors';
import { hasAuthenticatedAdminSession } from '../utils/admin-session';
import { TAX_SETTINGS_URL } from '../utils/automated-taxes';

// The plugin's Plugins-screen row handle: WordPress core sets data-plugin to
// "<dir>/<file>.php", which is stable across locales and across the plugin's own
// display-name rewrite (WC_Connect_Loader::maybe_rename_plugin()). Matched on the
// file name only, because wp-env names <dir> after the checkout folder, which
// differs in a worktree or a renamed clone.
const PLUGIN_MAIN_FILE = '/woocommerce-services.php';

test.describe( 'WooCommerce Tax smoke', () => {
	test( 'wp-admin dashboard loads without fatal PHP errors', async ( {
		page,
	} ) => {
		const response = await page.goto( 'wp-admin/' );
		expect( response?.ok() ).toBeTruthy();

		expect( await hasAuthenticatedAdminSession( page ) ).toBeTruthy();
		await expectNoPhpErrors( page );
	} );

	test( 'the plugin is active on the Plugins screen', async ( { page } ) => {
		const response = await page.goto( 'wp-admin/plugins.php' );
		expect( response?.ok() ).toBeTruthy();
		await expectNoPhpErrors( page );

		// The Deactivate row action ([id^="deactivate-"]) is present only when
		// the plugin is active, and is locale-independent.
		const pluginRow = page.locator(
			`tr[data-plugin$="${ PLUGIN_MAIN_FILE }"]`
		);
		await expect( pluginRow ).toBeVisible();
		await expect(
			pluginRow.locator( '[id^="deactivate-"]' )
		).toBeVisible();
	} );

	test( 'the tax settings screen loads without fatal PHP errors', async ( {
		page,
	} ) => {
		const response = await page.goto( TAX_SETTINGS_URL );
		expect( response?.ok() ).toBeTruthy();
		await expectNoPhpErrors( page );
	} );
} );
