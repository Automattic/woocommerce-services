import type { Page } from '@playwright/test';

export interface OrderTotals {
	id: number;
	total: string;
	total_tax: string;
	tax_lines: Array< { rate_id: number; label: string; tax_total: string } >;
}

interface ApiFetchWindow {
	wp?: {
		apiFetch?: < T >( options: { path: string } ) => Promise< T >;
	};
}

/**
 * Read an order back over the WooCommerce REST API, from a wc-admin page's
 * wp.apiFetch so the REST cookie nonce is already attached.
 *
 * @param page    Playwright page to drive.
 * @param orderId Order to read.
 */
export const getOrder = async (
	page: Page,
	orderId: number
): Promise< OrderTotals > => {
	await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax' );
	await page.waitForFunction(
		() => Boolean( ( window as ApiFetchWindow ).wp?.apiFetch ),
		undefined,
		{ timeout: 30_000 }
	);

	return page.evaluate( async ( id ) => {
		const apiFetch = ( window as ApiFetchWindow ).wp!.apiFetch!;

		try {
			return await apiFetch< OrderTotals >( {
				path: `/wc/v3/orders/${ id }`,
			} );
		} catch ( err ) {
			const detail =
				err && typeof err === 'object'
					? JSON.stringify( err )
					: String( err );
			throw new Error( `apiFetch GET /wc/v3/orders/${ id } failed: ${ detail }` );
		}
	}, orderId );
};
