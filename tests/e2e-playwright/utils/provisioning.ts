import type { Browser } from '@playwright/test';
import { ADMIN_STORAGE_STATE_PATH } from './paths';

// Shared store provisioning for the tax specs. Seeds, over the WooCommerce and
// WordPress REST APIs (driven from a wc-admin page's own wp.apiFetch, which
// already carries the REST nonce):
//
// - the store base in Austin, TX, with taxes enabled;
// - the logged-in admin's billing and shipping address in Houston, TX;
// - a $100 virtual product, so checkout needs no shipping method;
// - classic [woocommerce_cart] and [woocommerce_checkout] pages of the suite's
//   own, so the store's real cart and checkout pages are never rewritten and no
//   block-editor hydration timing is involved;
// - the Check payments gateway, which accepts virtual orders.
//
// Store and customer are in the SAME state on purpose: calculate_tax() returns
// no tax for a US cross-state order without calling TaxJar at all
// (class-wc-connect-taxjar-integration.php, "US from_state and to_state are
// different"). They are in DIFFERENT cities with different ZIPs on purpose too:
// origin and destination are built by separate code paths, and if the two
// addresses shared a ZIP a transposition between them would send an identical
// request and stay green.

export const STORE_ZIP = '78701';
export const CUSTOMER_ZIP = '77002';
export const CUSTOMER_CITY = 'Houston';
export const STATE = 'TX';
export const PRODUCT_PRICE = 100;

const STUB_PLUGIN = 'wc-services-e2e-tax-stub/wc-services-e2e-tax-stub';
const PRODUCT_NAME = 'E2E Taxable Product';
const PRODUCT_SKU = 'e2e-taxable-product';
const CART_PAGE_SLUG = 'e2e-tax-cart';
const CHECKOUT_PAGE_SLUG = 'e2e-tax-checkout';

export interface ProvisionedStore {
	productId: number;
	cartPageId: number;
	checkoutPageId: number;
}

interface ApiFetchWindow {
	wp?: {
		apiFetch?: < T >( options: {
			path: string;
			method?: string;
			data?: unknown;
		} ) => Promise< T >;
	};
}

export const ensureStoreProvisioned = async (
	browser: Browser,
	baseURL: string
): Promise< ProvisionedStore > => {
	const context = await browser.newContext( {
		baseURL,
		storageState: ADMIN_STORAGE_STATE_PATH,
	} );
	const page = await context.newPage();

	try {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=tax' );
		await page.waitForFunction(
			() => Boolean( ( window as ApiFetchWindow ).wp?.apiFetch ),
			undefined,
			{ timeout: 30_000 }
		);

		return await page.evaluate(
			async ( args ) => {
				const apiFetch = ( window as ApiFetchWindow ).wp!.apiFetch!;

				// Wrap every REST call so a rejection surfaces in Node with its
				// route and payload instead of an opaque "evaluate failed" -
				// @wordpress/api-fetch rejects with a plain object, not an Error.
				const call = async < T >( options: {
					path: string;
					method?: string;
					data?: unknown;
				} ): Promise< T > => {
					try {
						return await apiFetch< T >( options );
					} catch ( err ) {
						const detail =
							err && typeof err === 'object'
								? JSON.stringify( err )
								: String( err );
						throw new Error(
							`apiFetch ${ options.method ?? 'GET' } ${ options.path } failed: ${ detail }`
						);
					}
				};

				// Guard rail: everything below rewrites store settings and the
				// admin's own address, none of it restored afterwards. Refuse
				// unless the stub agrees this is a disposable test store - it
				// will not arm on a store with a real WordPress.com connection.
				await call( {
					path: '/wc-services-e2e-tax-stub/v1/arm',
					method: 'POST',
				} ).catch( ( err ) => {
					throw new Error(
						`Refusing to provision: could not arm the WooCommerce Tax E2E API stub ` +
							`("${ args.stubPlugin }"). Either it is not installed and active, or ` +
							`this site has a real WordPress.com connection. See .wp-env.json / ` +
							`qit.json. Underlying error: ${
								err instanceof Error ? err.message : String( err )
							}`
					);
				} );

				const status = await call< { armed: boolean } >( {
					path: '/wc-services-e2e-tax-stub/v1/status',
				} );
				if ( ! status.armed ) {
					throw new Error(
						'Refusing to provision: the WooCommerce Tax E2E API stub reports it is ' +
							'not armed even after /arm succeeded.'
					);
				}

				await call( {
					path: '/wc-services-e2e-tax-stub/v1/reset',
					method: 'POST',
				} );

				await call( {
					path: '/wc/v3/settings/general/batch',
					method: 'POST',
					data: {
						update: [
							{
								id: 'woocommerce_store_address',
								value: '100 Congress Ave',
							},
							{ id: 'woocommerce_store_city', value: 'Austin' },
							{
								id: 'woocommerce_store_postcode',
								value: args.storeZip,
							},
							{
								id: 'woocommerce_default_country',
								value: `US:${ args.state }`,
							},
							{ id: 'woocommerce_currency', value: 'USD' },
							{ id: 'woocommerce_calc_taxes', value: 'yes' },
						],
					},
				} );

				const currentUser = await call< { id: number } >( {
					path: '/wp/v2/users/me',
				} );
				const address = {
					first_name: 'E2E',
					last_name: 'Customer',
					address_1: '1 Main St',
					city: args.customerCity,
					state: args.state,
					postcode: args.customerZip,
					country: 'US',
				};
				await call( {
					path: `/wc/v3/customers/${ currentUser.id }`,
					method: 'PUT',
					data: {
						billing: {
							...address,
							email: 'e2e-customer@example.com',
							phone: '5555550100',
						},
						shipping: address,
					},
				} );

				// sold_individually caps the cart line at quantity 1, so a repeated
				// `?add-to-cart=` (or a rerun against the same store) cannot double
				// the totals the specs assert.
				const productData = {
					name: args.productName,
					type: 'simple',
					regular_price: String( args.productPrice ),
					sku: args.productSku,
					virtual: true,
					tax_status: 'taxable',
					tax_class: '',
					status: 'publish',
					sold_individually: true,
				};
				const existingProducts = await call< Array< { id: number } > >( {
					path: `/wc/v3/products?sku=${ encodeURIComponent(
						args.productSku
					) }`,
				} );
				const product = existingProducts[ 0 ]
					? await call< { id: number } >( {
							path: `/wc/v3/products/${ existingProducts[ 0 ].id }`,
							method: 'PUT',
							data: productData,
					  } )
					: await call< { id: number } >( {
							path: '/wc/v3/products',
							method: 'POST',
							data: productData,
					  } );

				const ensurePage = async ( slug: string, content: string ) => {
					const existing = await call< Array< { id: number } > >( {
						path: `/wp/v2/pages?slug=${ encodeURIComponent(
							slug
						) }&status=publish`,
					} );
					if ( existing[ 0 ] ) {
						return existing[ 0 ].id;
					}
					const created = await call< { id: number } >( {
						path: '/wp/v2/pages',
						method: 'POST',
						data: { title: slug, slug, content, status: 'publish' },
					} );
					return created.id;
				};

				const cartPageId = await ensurePage(
					args.cartSlug,
					'[woocommerce_cart]'
				);
				const checkoutPageId = await ensurePage(
					args.checkoutSlug,
					'[woocommerce_checkout]'
				);

				await call( {
					path: '/wc/v3/payment_gateways/cheque',
					method: 'PUT',
					data: { enabled: true },
				} );

				return {
					productId: product.id,
					cartPageId,
					checkoutPageId,
				};
			},
			{
				stubPlugin: STUB_PLUGIN,
				storeZip: STORE_ZIP,
				customerZip: CUSTOMER_ZIP,
				customerCity: CUSTOMER_CITY,
				state: STATE,
				productName: PRODUCT_NAME,
				productSku: PRODUCT_SKU,
				productPrice: PRODUCT_PRICE,
				cartSlug: CART_PAGE_SLUG,
				checkoutSlug: CHECKOUT_PAGE_SLUG,
			}
		);
	} finally {
		await context.close();
	}
};
