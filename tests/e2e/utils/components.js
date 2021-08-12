/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from './flows';
import { waitForSelectorAndText } from '.';

export const deleteAllPackages = async () => {
	await StoreOwnerFlow.login();
	await StoreOwnerFlow.openSettings( 'shipping', 'woocommerce-services-settings' );

	// Wait for "Add package" to finish loading, click "edit" once this session is loaded
	await waitForSelectorAndText('.button:not([disabled])', 'Add package');

	let editButton = await page.$( '.button.is-compact', { text: 'Edit' } );
	while ( editButton ) {
		await expect( page ).toClick( '.button.is-compact', { text: 'Edit' } );
		// Delete package
		await page.waitForSelector( '.packages__add-edit-title.form-section-heading', { text: 'Edit package' } );
		await expect( page ).toClick( '.button.packages__delete.is-scary.is-borderless', { text: 'Delete this package' } );
		editButton = await page.$( '.button.is-compact', { text: 'Edit' } );
	}
};

// Click save and wait until it's saved.
export const saveAndWait = async () => {
    await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
    await page.waitForSelector( '.button.is-primary.is-busy', { hidden: true } );
};
