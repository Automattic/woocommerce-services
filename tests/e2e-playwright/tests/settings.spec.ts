import { test, expect, type Page } from '@playwright/test';
import { expectNoPhpErrors } from '../utils/php-errors';
import { ensureStoreProvisioned } from '../utils/provisioning';
import { armStub } from '../utils/stub-api';
import {
	TAX_SETTINGS_URL,
	automatedTaxesField,
} from '../utils/automated-taxes';
import { expectSettingsSaved, saveButton } from '../utils/wc-settings';

// While automated taxes are on, add_tax_settings() disables the core tax options
// the integration manages (WC_Connect_TaxJar_Integration::$expected_options), and
// sanitize_tax_option() forces their values. "Calculate tax based on" is one of
// them; the disabled attribute on its native <select> is a locale-proof signal
// that the integration really took over rather than merely storing the option.
const TAX_BASED_ON_FIELD = '#woocommerce_tax_based_on';

const setAutomatedTaxes = async (
	page: Page,
	value: 'yes' | 'no'
) => {
	await page.goto( TAX_SETTINGS_URL );
	await expectNoPhpErrors( page );
	// wc-enhanced-select: the native <select> sits behind the selectWoo widget.
	await automatedTaxesField( page ).selectOption( value, { force: true } );
	await saveButton( page ).click();
	await expectSettingsSaved( page );
	await expectNoPhpErrors( page );
};

test.describe( 'WooCommerce Tax automated taxes setting', () => {
	test.beforeAll( async ( { browser }, testInfo ) => {
		const baseURL = testInfo.project.use.baseURL;
		if ( ! baseURL ) {
			throw new Error(
				'Playwright baseURL is not configured. Set BASE_URL and retry.'
			);
		}

		// The field only exists for a store in a TaxJar-supported country with
		// taxes enabled, and only once the stub has armed the integration.
		await ensureStoreProvisioned( browser, baseURL );
	} );

	test.beforeEach( async ( { page } ) => {
		await armStub( page );
	} );

	test( 'turns automated taxes off and on through the admin UI, and the core options follow', async ( {
		page,
	} ) => {
		await setAutomatedTaxes( page, 'no' );

		await page.goto( TAX_SETTINGS_URL );
		await expect( automatedTaxesField( page ) ).toHaveValue( 'no' );
		await expect( page.locator( TAX_BASED_ON_FIELD ) ).toBeEnabled();

		// Ends enabled: the tax spec needs it, and enableAutomatedTaxes() there
		// would only have to turn it back on.
		await setAutomatedTaxes( page, 'yes' );

		await page.goto( TAX_SETTINGS_URL );
		await expect( automatedTaxesField( page ) ).toHaveValue( 'yes' );
		await expect( page.locator( TAX_BASED_ON_FIELD ) ).toBeDisabled();
		await expect( page.locator( TAX_BASED_ON_FIELD ) ).toHaveValue(
			'shipping'
		);
	} );
} );
