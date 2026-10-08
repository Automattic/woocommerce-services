import type { Page } from '@playwright/test';

// Thin client for the E2E API stub's own REST namespace, driven from a
// wc-admin page's wp.apiFetch so the REST cookie nonce is already attached.
//
// Specs use this to read back what the plugin actually SENT to TaxJar. The stub
// prices every line item at fixed rates whatever the request says, so without
// this an assertion on the rendered tax proves only that the stub replied - it
// would stay green if the plugin sent the wrong origin, destination or price.

// Any wc-admin screen loads wp-api-fetch; the tax tab is the one the specs
// already visit.
const API_HOST_PAGE = 'wp-admin/admin.php?page=wc-settings&tab=tax';

const NAMESPACE = '/wc-services-e2e-tax-stub/v1';

export interface RecordedRequest {
	// Path below the Connect server URL, e.g. "taxjar/v2/taxes".
	path: string;
	// The decoded JSON body the plugin sent.
	body: Record< string, unknown >;
}

export interface StubStatus {
	armed: boolean;
	// Requests answered since the last reset, oldest first.
	requests: RecordedRequest[];
}

interface ApiFetchWindow {
	wp?: {
		apiFetch?: < T >( options: {
			path: string;
			method?: string;
		} ) => Promise< T >;
	};
}

const callStub = async < T >(
	page: Page,
	path: string,
	method: 'GET' | 'POST'
): Promise< T > => {
	await page.goto( API_HOST_PAGE );
	await page.waitForFunction(
		() => Boolean( ( window as ApiFetchWindow ).wp?.apiFetch ),
		undefined,
		{ timeout: 30_000 }
	);

	return page.evaluate(
		async ( args ) => {
			const apiFetch = ( window as ApiFetchWindow ).wp!.apiFetch!;

			try {
				return await apiFetch( {
					path: args.path,
					method: args.method,
				} );
			} catch ( err ) {
				// @wordpress/api-fetch rejects with a plain object, not an
				// Error, which would otherwise serialize to nothing across
				// evaluate().
				const detail =
					err && typeof err === 'object'
						? JSON.stringify( err )
						: String( err );
				throw new Error(
					`apiFetch ${ args.method } ${ args.path } failed: ${ detail }`
				);
			}
		},
		{ path, method }
	) as Promise< T >;
};

/**
 * Read the stub's armed state and the requests it has answered.
 *
 * @param page Playwright page to drive.
 */
export const getStubStatus = ( page: Page ): Promise< StubStatus > =>
	callStub< StubStatus >( page, `${ NAMESPACE }/status`, 'GET' );

/**
 * Arm the stub. Refuses (409) on a store with a real WordPress.com connection.
 *
 * @param page Playwright page to drive.
 */
export const armStub = async ( page: Page ): Promise< void > => {
	await callStub< { armed: boolean } >( page, `${ NAMESPACE }/arm`, 'POST' );
};

/**
 * Disarm the stub and undo what arming changed. A no-op when not armed.
 *
 * @param page Playwright page to drive.
 */
export const disarmStub = async ( page: Page ): Promise< void > => {
	await callStub< { armed: boolean } >(
		page,
		`${ NAMESPACE }/disarm`,
		'POST'
	);
};

/**
 * Clear cached TaxJar responses, recorded requests and the admin's cart.
 *
 * @param page Playwright page to drive.
 */
export const resetStub = async ( page: Page ): Promise< void > => {
	await callStub< { reset: boolean } >(
		page,
		`${ NAMESPACE }/reset`,
		'POST'
	);
};
