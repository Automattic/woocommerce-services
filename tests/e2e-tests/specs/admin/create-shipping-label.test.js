/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct, loginAndConfirmExtensionActivation } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { setCheckbox, settingsPageSaveChanges, uiUnblocked, verifyCheckboxIsSet } from "../../utils";

describe( 'Create shipping label', () => {
	beforeAll( async () => {
		await loginAndConfirmExtensionActivation();
		console.log( 'I am logged in!' );
		await createSimpleProduct();
		console.log( 'Simple product is created!' );

		// Go to general settings page
		await StoreOwnerFlow.openSettings( 'general' );

		// Set base location with state CA.
		await expect( page ).toSelect( 'select[name="woocommerce_default_country"]', 'United States (US) — California' );
		// Sell to all countries
		await expect( page ).toSelect( '#woocommerce_allowed_countries', 'Sell to all countries' );
		// Set currency to USD
		await expect( page ).toSelect( '#woocommerce_currency', 'United States (US) dollar ($)' );
		// Tax calculation should have been enabled by another test - no-op
		// Save
		await settingsPageSaveChanges();
		console.log( 'General settings have been set and saved!' );

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } ),
			expect( page ).toMatchElement( 'select[name="woocommerce_default_country"]', { text: 'United States (US) — California' } ),
			expect( page ).toMatchElement( '#woocommerce_allowed_countries', { text: 'Sell to all countries' } ),
			expect( page ).toMatchElement( '#woocommerce_currency', { text: 'United States (US) dollar ($)' } ),
		] );

		// Enable COD payment method
		await StoreOwnerFlow.openSettings( 'checkout', 'cod' );
		await setCheckbox( '#woocommerce_cod_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_cod_enabled' );
		console.log( 'Payment settings have been set and saved!' );

		await StoreOwnerFlow.logout();
		console.log( 'I am logged out!' );
	} );

	it( 'should add item to cart and display cart item in order review', async () => {
		await CustomerFlow.goToShop();
		console.log( 'I am on the shop page!' );
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		await CustomerFlow.goToCheckout();
		await CustomerFlow.productIsInCheckout( 'Simple product', 1, 9.99 );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$9.99' } );
	} );

	it( 'should allow customer to place order', async () => {

		await expect( page ).toFill( '#billing_first_name', 'John' );
		await expect( page ).toFill( '#billing_last_name', 'Doe' );
		await expect( page ).toFill( '#billing_company', 'Automattic' );
		await expect( page ).toFill( '#billing_email', 'john.doe@example.com' );
		await expect( page ).toFill( '#billing_phone', '123456789' );
		await expect( page ).toSelect( '#billing_country', 'United States (US)' );
		await expect( page ).toFill( '#billing_address_1', 'addr 1' );
		await expect( page ).toFill( '#billing_address_2', 'addr 2' );
		await expect( page ).toFill( '#billing_city', 'San Francisco' );
		await expect( page ).toSelect( '#billing_state', 'California' );
		await expect( page ).toFill( '#billing_postcode', '94107' );
		await uiUnblocked();

		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
		await expect( page ).toMatchElement( '.payment_method_cod', { text: 'Pay with cash upon delivery.' } );
		await uiUnblocked();
		await CustomerFlow.placeOrder();

		await expect( page ).toMatch( 'Order received' );
	} );

	it( 'should login as Admin and navigate to order page', async () => {
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openExistingOrderPage();
	} );

	it( 'should create shipping label', async () => {
		// Click on Create shipping label button
		await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root .button.is-primary' );
		await page.waitForSelector( '.dialog__content' );

		// Work on origin address section
		await expect( page ).toClick( '.gridicons-chevron-down' );
		await page.waitForSelector( '.address-step__actions' );

		// Fill in origin address details
		await expect( page ).toFill( '#origin_address', '1480 York Ave' );
		await expect( page ).toFill( '#origin_city', 'New York' );
		await expect( page ).toSelect( '#origin_state', 'New York' );
		await expect( page ).toFill( '#origin_postcode', '10075' );
		await expect( page ).toFill( '#origin_country', 'United States (US)' );

		// Verify address
		await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root .address-step__actions .form-button:first-child' );
		await page.waitForSelector( '.dialog__content' );

		// Use selected address
		await expect( page ).toClick( '.address-step__suggestion-title' );
		await page.waitForSelector( '.dialog__content' );

		await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root .address-step__actions .form-button:first-child' );
		await page.waitForSelector( '.foldable-card__content' );

		// Work on destination address section
		await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root input[type="radio"]' );
		// Use selected address
		await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root .address-step__actions .form-button:first-child' );
		await page.waitForSelector( '.packages-step__contents' );
	} );
} );
