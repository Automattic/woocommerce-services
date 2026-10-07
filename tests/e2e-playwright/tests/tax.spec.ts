import { test, expect, type Page } from '@playwright/test';
import { expectNoPhpErrors } from '../utils/php-errors';
import { enableAutomatedTaxes } from '../utils/automated-taxes';
import { getOrder } from '../utils/orders';
import {
	CUSTOMER_ZIP,
	PRODUCT_PRICE,
	STATE,
	STORE_ZIP,
	ensureStoreProvisioned,
} from '../utils/provisioning';
import { armStub, getStubStatus, resetStub } from '../utils/stub-api';

// Tax-at-cart and tax-at-checkout coverage: with automated taxes on, the plugin
// asks TaxJar (through the Connect server proxy) for rates, writes them into the
// WooCommerce tax tables, and WooCommerce applies them. TaxJar is answered by the
// E2E stub plugin (tests/e2e-playwright/support/wc-services-e2e-tax-stub), so no
// network or WordPress.com connection is needed and the amounts are fixed.

// The stub's two rate components, from wc_services_e2e_tax_stub_rates(): a 6.25%
// state rate and a 2% city rate. The plugin writes each as its own rate row, so
// the cart shows two tax lines.
const STATE_TAX = ( PRODUCT_PRICE * 0.0625 ).toFixed( 2 ); // 6.25
const CITY_TAX = ( PRODUCT_PRICE * 0.02 ).toFixed( 2 ); // 2.00
const TOTAL_TAX = ( PRODUCT_PRICE * 0.0825 ).toFixed( 2 ); // 8.25
const ORDER_TOTAL = ( PRODUCT_PRICE * 1.0825 ).toFixed( 2 ); // 108.25

let productId: number;
let cartPageId: number;
let checkoutPageId: number;

// The amount cell of each itemized tax row, as a sorted list of numbers.
// Comparing numbers keeps the assertion independent of currency formatting.
const taxRowAmounts = async ( page: Page, scope: string ) => {
	const cells = page.locator( `${ scope } tr.tax-rate td` );
	const texts = await cells.allInnerTexts();
	return texts
		.map( ( text ) => Number( text.replace( /[^0-9.]/g, '' ) ) )
		.sort( ( a, b ) => a - b );
};

const addProductToCart = async ( page: Page ) => {
	await page.goto( `?add-to-cart=${ productId }` );
	await expectNoPhpErrors( page );
};

test.describe( 'WooCommerce Tax automated taxes', () => {
	test.beforeAll( async ( { browser }, testInfo ) => {
		const baseURL = testInfo.project.use.baseURL;
		if ( ! baseURL ) {
			throw new Error(
				'Playwright baseURL is not configured. Set BASE_URL and retry.'
			);
		}

		const store = await ensureStoreProvisioned( browser, baseURL );
		productId = store.productId;
		cartPageId = store.cartPageId;
		checkoutPageId = store.checkoutPageId;

		expect( productId ).toBeGreaterThan( 0 );
		expect( cartPageId ).toBeGreaterThan( 0 );
		expect( checkoutPageId ).toBeGreaterThan( 0 );
	} );

	// Each test re-arms and resets, so a TaxJar response cached by an earlier
	// test cannot let an assertion pass without the stub being asked again.
	test.beforeEach( async ( { page } ) => {
		await armStub( page );
		await resetStub( page );
		await enableAutomatedTaxes( page );
	} );

	test( 'adds the TaxJar rates to the cart totals', async ( { page } ) => {
		await addProductToCart( page );
		await page.goto( `?page_id=${ cartPageId }` );
		await expectNoPhpErrors( page );

		const totals = page.locator( '.cart_totals' );
		await expect( totals.locator( '.order-total' ) ).toContainText(
			ORDER_TOTAL
		);
		expect( await taxRowAmounts( page, '.cart_totals' ) ).toEqual( [
			Number( CITY_TAX ),
			Number( STATE_TAX ),
		] );
	} );

	test( 'sends TaxJar the store as the origin and the customer as the destination', async ( {
		page,
	} ) => {
		await addProductToCart( page );
		await page.goto( `?page_id=${ cartPageId }` );
		// Wait for the taxed total, so the request has been made before the
		// capture is read back.
		await expect( page.locator( '.cart_totals .order-total' ) ).toContainText(
			ORDER_TOTAL
		);

		// The stub prices on its own fixed rates and ignores the request, so the
		// assertion above only proves the stub replied. Assert on what the plugin
		// built - that is what a regression breaks.
		const status = await getStubStatus( page );
		expect( status.armed ).toBe( true );

		const request = status.requests
			.filter( ( recorded ) => recorded.path === 'taxjar/v2/taxes' )
			.pop();
		expect( request, 'no taxjar/v2/taxes request was recorded' ).toBeTruthy();

		const body = request!.body as {
			to_country?: string;
			to_state?: string;
			to_zip?: string;
			nexus_addresses?: Array< { country?: string; state?: string; zip?: string } >;
			line_items?: Array< { quantity?: unknown; unit_price?: unknown } >;
		};

		// The store address goes out as the nexus address
		// (WC_Connect_TaxJar_Integration::calculate_tax()), replacing from_*.
		// Store and customer ZIPs differ on purpose - see provisioning.ts.
		expect( body.nexus_addresses?.[ 0 ] ).toMatchObject( {
			country: 'US',
			state: STATE,
			zip: STORE_ZIP,
		} );
		expect( body.to_country ).toBe( 'US' );
		expect( body.to_state ).toBe( STATE );
		expect( body.to_zip ).toBe( CUSTOMER_ZIP );

		expect( body.line_items ).toHaveLength( 1 );
		expect( Number( body.line_items![ 0 ].quantity ) ).toBe( 1 );
		expect( Number( body.line_items![ 0 ].unit_price ) ).toBe( PRODUCT_PRICE );
	} );

	test( 'places an order whose total includes the tax', async ( { page } ) => {
		await addProductToCart( page );
		await page.goto( `?page_id=${ checkoutPageId }` );
		await expectNoPhpErrors( page );

		const review = page.locator( '#order_review' );
		await expect( review.locator( '.order-total' ) ).toContainText(
			ORDER_TOTAL
		);

		// The billing fields are prefilled from the admin's customer record
		// (provisioning.ts). Wait out the update_order_review overlay so the
		// click lands on a form that is ready to submit.
		await page.locator( '#payment_method_cheque' ).check();
		await expect( page.locator( '.blockUI.blockOverlay' ) ).toHaveCount( 0 );
		await page.locator( '#place_order' ).click();

		// order-received is an endpoint of the store's own checkout page:
		// /checkout/order-received/<id>/ under pretty permalinks, and
		// ?page_id=<id>&order-received=<id> under plain ones.
		await page.waitForURL( /order-received[=/]\d+/, {
			timeout: 60_000,
			waitUntil: 'commit',
		} );
		const orderId = Number(
			/order-received[=/](\d+)/.exec( page.url() )?.[ 1 ]
		);
		expect( orderId ).toBeGreaterThan( 0 );

		const order = await getOrder( page, orderId );
		expect( order.total ).toBe( ORDER_TOTAL );
		expect( order.total_tax ).toBe( TOTAL_TAX );
		expect(
			order.tax_lines.map( ( line ) => Number( line.tax_total ) ).sort( ( a, b ) => a - b )
		).toEqual( [ Number( CITY_TAX ), Number( STATE_TAX ) ] );

		// The reset in beforeEach removed the rate rows earlier answers wrote, so
		// the totals above cannot come from core's own rate lookup alone. This
		// says so directly when the TaxJar path never ran.
		const status = await getStubStatus( page );
		expect(
			status.requests.some(
				( recorded ) => recorded.path === 'taxjar/v2/taxes'
			),
			'no taxjar/v2/taxes request was recorded for this order'
		).toBe( true );
	} );
} );
