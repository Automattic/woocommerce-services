/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from "./flows";
import { clickTab, clickReactButton } from "./index";

const verifyAndPublish = async () => {
	// Wait for auto save
	await page.waitForSelector( '#sample-permalink' );

	// Publish product
	console.log( 'about to save the product' )
	await clickReactButton( '#publish' );
	console.log( 'product saved!' )

	// Verify
	await page.waitForSelector( '.updated.notice', { text: 'Product published.', timeout: 1500 }  );
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Product published.' } );
	console.log( 'product saved confirmed!' )
};

/**
 * Create simple product.
 */
const createSimpleProduct = async () => {
	// Go to "add product" page
	await StoreOwnerFlow.openNewProduct();

	// Make sure we're on the add product page
	await expect( page ).toMatchElement( '.wp-heading-inline', { text: 'Add new product' } )

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
	const slug = 'woocommerce-services';
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
