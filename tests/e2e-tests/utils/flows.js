const baseUrl = process.env.WP_BASE_URL;
const adminUserName = process.env.WP_ADMIN_USER_NAME;
const adminUserPassword = process.env.WP_ADMIN_USER_PW;

const WP_ADMIN_LOGIN = baseUrl + '/wp-login.php';
const WP_ADMIN_PLUGINS_PAGE = baseUrl + '/wp-admin/plugins.php';
const WP_ADMIN_ORDERS_PAGE = baseUrl + '/wp-admin/edit.php?post_type=shop_order';
const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + '/wp-admin/admin.php?page=wc-settings&tab=';
const WP_ADMIN_WC_STATUS = baseUrl + '/wp-admin/admin.php?page=wc-status&tab=';

const SHOP_PAGE = baseUrl + '/shop/';
const SHOP_CHECKOUT_PAGE = baseUrl + '/checkout/';

const CustomerFlow = {
	addToCartFromShopPage: async ( productTitle ) => {
		const addToCartXPath = `//li[contains(@class, "type-product") and a/h2[contains(text(), "${ productTitle }")]]` +
			'//a[contains(@class, "add_to_cart_button") and contains(@class, "ajax_add_to_cart")';

		const [ addToCartButton ] = await page.$x( addToCartXPath + ']' );
		addToCartButton.click();

		await page.waitFor( addToCartXPath + ' and contains(@class, "added")]' );
	},

	goToCheckout: async () => {
		await page.goto( SHOP_CHECKOUT_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	goToShop: async () => {
		await page.goto(SHOP_PAGE, {
			waitUntil: 'networkidle0',
		});
	},

	placeOrder: async () => {
		await Promise.all( [
			expect( page ).toClick( '#place_order' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	productIsInCheckout: async ( productTitle, quantity, total ) => {
		await expect( page ).toMatchElement( '.cart_item .product-name', { text: productTitle } );
		await expect( page ).toMatchElement( '.cart_item .product-quantity', { text: `× ${quantity}` } );
		await expect( page ).toMatchElement( '.cart_item .product-total', { text: `\$${ total }` } );
	},
};

const StoreOwnerFlow = {
    login: async () => {
        await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await page.type( '#user_login', adminUserName );
		await page.type( '#user_pass', adminUserPassword );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		await page.goto( baseUrl + '/wp-login.php?action=logout', {
			waitUntil: 'networkidle0',
		} );

		await expect( page ).toMatch( 'You are attempting to log out' );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'a' ),
		] );
	},

	openExistingOrderPage: async () => {
		console.log( 'I am about to visit the orders page!' )
		await page.goto( WP_ADMIN_ORDERS_PAGE, {
			waitUntil: 'networkidle0',
		} );

		console.log( 'I am in the orders page!' )
		await expect( page.title() ).resolves.toMatch( 'Orders' );
		await page.click( 'a.order-view' );
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );

		await expect( page.title() ).resolves.toMatch( /Edit Order ‹.*/i );
		await page.waitForSelector(  '.woocommerce-order-data__heading', { text: /Order #[0-9]+ details/ }   )

		// Load the shipping address from the billing address
		await page.click( '#order_data > div.order_data_column_container > div:nth-child(3) > h3 > a.edit_address' );
		await page.waitForSelector( '.billing-same-as-shipping', { visible: true } );
		await page.click( '.billing-same-as-shipping', { text: 'Copy billing address' } );
		await page.click( '.button.save_order' );

		await page.waitForSelector( '.updated.notice', { text: 'Order updated.' }  );
		await expect( page ).toMatchElement( '.updated.notice', { text: 'Order updated.' } );
	},

	openNewProduct: async () => {
		await page.goto( WP_ADMIN_NEW_PRODUCT, {
			waitUntil: 'networkidle0',
		} );
	},

	openPluginsPage: async () => {
		await page.goto( WP_ADMIN_PLUGINS_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},

	openSettings: async ( tab, section = null ) => {
		let settingsUrl = WP_ADMIN_WC_SETTINGS + tab;

		if ( section ) {
			settingsUrl += `&section=${ section }`;
		}

		await page.goto( settingsUrl, {
			waitUntil: 'networkidle0',
		} );
	},

	openStatus: async ( tab, section = null ) => {
		let statusUrl = WP_ADMIN_WC_STATUS + tab;

		if ( section ) {
			statusUrl += `&section=${ section }`;
		}

		await page.goto( statusUrl, {
			waitUntil: 'networkidle0',
		} );
	},
}

module.exports = {
	CustomerFlow,
	StoreOwnerFlow
};
