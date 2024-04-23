/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils';

describe( 'Store admin can login and make sure WooCommerce Shipping & Tax extension is activated', () => {
	it( 'Can activate WooCommerce Shipping & Tax extension if it is deactivated' , async () => {
		const slug = 'woocommerce-services'
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.updateWPDB();
		await StoreOwnerFlow.updateWPDB();
		await StoreOwnerFlow.openPluginsPage();
		const enableLink = await page.$( `a#activate-${ slug }` );
		const disableLink = await page.$( `a#deactivate-${ slug }` );

		if ( ! enableLink && disableLink ) {
			return;
		}

		await page.waitForSelector( `a#activate-${ slug }` );

		await page.click( `a#activate-${ slug }` );

		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	});
} );
