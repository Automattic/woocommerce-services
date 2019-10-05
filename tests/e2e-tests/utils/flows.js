const baseUrl = process.env.WP_BASE_URL;
const adminUserName = process.env.WP_ADMIN_USER_NAME;
const adminUserPassword = process.env.WP_ADMIN_USER_PW;

const WP_ADMIN_LOGIN = baseUrl + '/wp-login.php';
const WP_ADMIN_PLUGINS_PAGE = baseUrl + '/wp-admin/plugins.php';
const WP_ADMIN_EXISTING_ORDER_PAGE = baseUrl + '/wp-admin/post.php?post=82&action=edit';
const WP_ADMIN_NEW_PRODUCT = baseUrl + '/wp-admin/post-new.php?post_type=product';
const WP_ADMIN_WC_SETTINGS = baseUrl + '/wp-admin/admin.php?page=wc-settings&tab=';

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
		const checkoutItemXPath =
			'//tr[@class="cart_item" and ' +
			`.//td[contains(., "${ productTitle }") and contains(., "× ${ quantity }")] and ` +
			`.//td[contains(., "${ total }")]` +
			']';

		await expect( page.$x( checkoutItemXPath ) ).resolves.toHaveLength( 1 );
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
		await page.goto( WP_ADMIN_EXISTING_ORDER_PAGE, {
			waitUntil: 'networkidle0',
		} );
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
}

module.exports = {
	CustomerFlow,
	StoreOwnerFlow
};
