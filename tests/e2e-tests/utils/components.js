/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from "./flows";
import { clickTab } from "./index";

const verifyAndPublish = async () => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Product published.' } );
};

/**
 * Create simple product.
 */
const createSimpleProduct = async () => {
	// Go to "add product" page
	await StoreOwnerFlow.openNewProduct();

	// Make sure we're on the add order page
	await expect(page.title()).resolves.toMatch('Add new product');

	// Set product data
	await expect(page).toFill('#title', 'Simple product');
	await clickTab('General');
	await expect(page).toFill('#_regular_price', '9.99');

	await verifyAndPublish();
};

/**
 * Login and confirm that extension is activated on the site.
 */
const loginAndConfirmExtensionActivation = async () => {
	let slug = 'woocommerce-services';
	await StoreOwnerFlow.login();
	await StoreOwnerFlow.openPluginsPage();
	const disableLink = await page.$( `tr[data-slug="${ slug }"] .deactivate a` );
	if ( disableLink ) {
		return;
	}
	await page.click( `tr[data-slug="${ slug }"] .activate a` );
	await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
};

export { createSimpleProduct, loginAndConfirmExtensionActivation };
