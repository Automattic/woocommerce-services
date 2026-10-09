import { expect, type Page } from '@playwright/test';
import { expectNoPhpErrors } from './php-errors';
import { expectSettingsSaved, saveButton } from './wc-settings';

export const TAX_SETTINGS_URL = 'wp-admin/admin.php?page=wc-settings&tab=tax';

// The "Automated taxes" select WC_Connect_TaxJar_Integration::add_tax_settings()
// adds to the core tax screen. It only renders once the integration has
// initialised, which needs the E2E stub armed (see the stub's header).
export const automatedTaxesField = ( page: Page ) =>
	page.locator( '#wc_connect_taxes_enabled' );

/**
 * Turn automated taxes on through the tax settings screen. Idempotent: a store
 * that already has them on is left as it is, without a save.
 *
 * @param page Playwright page to drive.
 */
export const enableAutomatedTaxes = async ( page: Page ) => {
	await page.goto( TAX_SETTINGS_URL );
	await expectNoPhpErrors( page );

	const field = automatedTaxesField( page );
	await expect( field ).toBeAttached();

	if ( ( await field.inputValue() ) === 'yes' ) {
		return;
	}

	// The field is a wc-enhanced-select, so selectWoo hides the native <select>
	// behind its own widget. Setting the native element still submits the value,
	// and its option values are locale-independent where the widget's text is not.
	await field.selectOption( 'yes', { force: true } );
	await saveButton( page ).click();
	await expectSettingsSaved( page );
	await expectNoPhpErrors( page );
};
