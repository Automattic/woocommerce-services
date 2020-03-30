/**
 * Internal dependencies
 */
import { clickReactButton } from '../../utils/index';
import { StoreOwnerFlow } from "../../utils/flows";

describe( 'Shipping label settings', () => {

	it( 'Can toggle to disable shipping labels' , async () => {
		let slug = 'woocommerce-services';
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await page.waitForSelector('.form-toggle__switch');
        await expect(page).toClick('.form-toggle__switch');
        await page.waitForSelector('.label-settings__cards-label form-label', {
            hidden: true
        });
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        // Clicking save should persist the hidden state of the section.
        await page.waitForSelector('.label-settings__cards-label form-label', {
            hidden: true
        });
    });



} );
