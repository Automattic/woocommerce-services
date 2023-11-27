/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils';

describe( 'Store admin can login and make sure WooCommerce Shipping & Tax extension is activated', () => {
	it( 'Can activate WooCommerce Shipping & Tax extension if it is deactivated' , async () => {
		const slug = 'woocommerce-services'
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.updateWPDB();
		await StoreOwnerFlow.openPluginsPage();
		const disableLink = await page.$( `tr[data-slug="${ slug }"] .deactivate a` );
		if ( disableLink ) {
			return;
		}

		await page.waitForSelector( `tr[data-slug="${ slug }"] .activate a` );

		await page.click( `tr[data-slug="${ slug }"] .activate a` );

		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	});
} );
