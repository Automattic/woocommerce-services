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

		const activateButton = await page.click( `tr[data-slug="${ slug }"] .activate a` );
		if ( activateButton ) {
			await StoreOwnerFlow.openPluginsPage();
			await page.click( `tr[data-slug="${ slug }"] .activate a` );
		}
		const deactivateButton = await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
		if ( deactivateButton ) {
			await StoreOwnerFlow.openPluginsPage();
			await page.click( `tr[data-slug="${ slug }"] .deactivate a` );
		}
	});
} );
