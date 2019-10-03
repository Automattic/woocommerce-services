/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from "../../utils/flows";

describe( 'Store admin can login and make sure WooCommerce Service extension is activated', () => {
	
	it( 'Can activate WooCommerce Service extension if it is deactivated' , async () => {
		let slug = 'woocommerce-services';
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openPluginsPage();
		const disableLink = await page.$( `tr[data-slug="${ slug }"] .2deactivate a` );
		if ( disableLink ) {
			return;
		}
		await page.click( `tr[data-slug="${ slug }"] .activate a` );
		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	});

} );
