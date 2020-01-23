/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct, loginAndConfirmExtensionActivation } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { clickReactButton } from '../../utils/index';
import { setCheckbox, settingsPageSaveChanges, uiUnblocked, verifyCheckboxIsSet } from "../../utils";

describe( 'Create shipping label', () => {
	beforeAll( async () => {
		page.setDefaultTimeout( 0 );
		page.setDefaultNavigationTimeout( 0 );
		page.on( "dialog", ( dialog ) => {
  	  	  	console.log( `accepting dialog: ${ dialog.message() }` );
  	  	  	dialog.accept();
		} );
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

		// Add missing WooCommerce pages
		await StoreOwnerFlow.openStatus( 'tools' );
		await clickReactButton( '.button.install_pages' );
		await page.waitForSelector( '.updated.inline', { text: 'All missing WooCommerce pages successfully installed' } );
		console.log( 'Missing WooCommerce pages installed!' );


		await StoreOwnerFlow.logout();
		console.log( 'I am logged out!' );
	} );

	it( 'should add item to cart and display cart item in order review', async () => {
		await CustomerFlow.goToShop();
		console.log( 'I am on the shop page!' );
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		console.log( 'Product added to the cart!' );
		await CustomerFlow.goToCheckout();
		console.log( 'I am on the checkout page!' );
		await CustomerFlow.productIsInCheckout( 'Simple product', 1, 9.99 );
		console.log( 'Product is in the order review page!' );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$9.99' } );
	} );

	it( 'should allow customer to place order', async () => {
		console.log( 'Filling out billing' );
		await expect( page ).toFill( '#billing_first_name', 'John' );
		console.log('#billing_last_name');
		await expect( page ).toFill( '#billing_last_name', 'Doe' );
		console.log('#billing_company');
		await expect( page ).toFill( '#billing_company', 'Automattic' );
		console.log('#billing_email');
		await expect( page ).toFill( '#billing_email', 'john.doe@example.com' );
		console.log('#billing_phone');
		await expect( page ).toFill( '#billing_phone', '123456789' );
		console.log('#billing_country');
		await expect( page ).toSelect( '#billing_country', 'United States (US)' );
		console.log('#billing_address_1');
		await expect( page ).toFill( '#billing_address_1', 'addr 1' );
		console.log('#billing_address_2');
		await expect( page ).toFill( '#billing_address_2', 'addr 2' );
		console.log('#billing_city');
		await expect( page ).toFill( '#billing_city', 'San Francisco' );
		console.log('#billing_state');
		await expect( page ).toSelect( '#billing_state', 'California' );
		console.log('#billing_postcode');
		await expect( page ).toFill( '#billing_postcode', '94107' );
		console.log('uiUnblocked');
		await uiUnblocked();

		console.log('fill out billing delivery');
		await expect( page ).toClick( '.wc_payment_method label', { text: 'Cash on delivery' } );
		console.log('Have cash on delivery');
		await expect( page ).toMatchElement( '.payment_method_cod', { text: 'Pay with cash upon delivery.' } );
		console.log('fill out billing delivery');
		await uiUnblocked();
		console.log( 'Placing order' );
		await CustomerFlow.placeOrder();
		console.log( 'order placed' );
		await expect( page ).toMatch( 'Order received' );
	} );

	it( 'should login as Admin and navigate to order page', async () => {
		console.log( 'I am about to log in!' );
		await StoreOwnerFlow.login();
		console.log( 'I am logged in!' );
		await StoreOwnerFlow.openExistingOrderPage();
	} );

	it( 'should create shipping label', async () => {
		// Click on Create shipping label button
		await clickReactButton( '.shipping-label__new-label-button' );
		await page.waitForSelector( '.dialog__content' );


		const isOriginAddressReady = await page.waitForSelector( '.is-success', { text: 'NEW YORK, NY  10075' } );
		if ( !isOriginAddressReady ) {
			// Work on origin address section
			await page.waitForSelector( '.address-step__actions' );

			// Fill in origin address details
			await expect( page ).toFill( '#origin_address', '1480 York Ave' );
			await expect( page ).toFill( '#origin_city', 'New York' );
			await expect( page ).toSelect( '#origin_state', 'New York' );
			await expect( page ).toFill( '#origin_postcode', '10075' );

			// Verify address
			await expect( page ).toClick( '.wp-core-ui.wp-admin .wcc-root .address-step__actions .form-button:first-child' );
			await page.waitForSelector( '.is-success', { text: 'NEW YORK, NY  10075' } );
		}

		// Use selected address
		await page.waitForSelector( '.button.is-primary.is-borderless', { text: 'Use address as entered' } );
		await expect( page ).toClick( '.button.is-primary.is-borderless', { text: 'Use address as entered' } );
		await page.waitForSelector( '.is-success', { text: 'San Francisco, CA  94107' } );

		const selectAPackageType = await page.$( '.packages-step__no-packages a', { text: 'Select a package type' } );

		if ( selectAPackageType ) {
			await expect( page ).toClick( 'Select a package type' );
			await selectAPackageType.click();
			await expect( page ).toFill( '#weight_default_box', '1' );

			await page.waitForSelector( '.packages__add-edit-dialog' );

			await expect( page ).toFill( '.packages__add-edit-dialog #name', 'My Package' );
			await expect( page ).toFill( '.packages__add-edit-dialog #length', '1' );
			await expect( page ).toFill( '.packages__add-edit-dialog #width', '1' );
			await expect( page ).toFill( '.packages__add-edit-dialog #height', '1' );
			await expect( page ).toFill( '.packages__add-edit-dialog #box_weight', '1' );

			await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );

		}

		await expect( page ).toClick( '.button.is-primary', { text: 'Use these packages' } );
		await page.waitForSelector( '.is-success', { text: '1 item in 1 package: 2 kg total' } );

		await page.waitForSelector( '#inspector-radio-control-0-0' );
		await expect( page ).toClick( '#inspector-radio-control-0-0' );

		await expect( page ).toClick( '.button.is-primary', { text: 'Buy shipping labels' } );

		await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping label was purchased successfully' } )
	} );
} );
