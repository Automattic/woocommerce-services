/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';
import sinon from 'sinon';
import nock from 'nock';

/**
 * Internal dependencies
 */
import {
	openPrintingFlow,
	convertToApiPackage,
	submitAddressForNormalization,
	confirmAddressSuggestion,
	getDefaultBoxSelection,
	getDefaultServiceSelection,
} from '../actions';
import {
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
	WOOCOMMERCE_SERVICES_SHIPPING_LABEL_CONFIRM_ADDRESS_SUGGESTION,
} from '../../action-types';
import * as selectors from '../selectors';

const orderId = 1;
const siteId = 123456;
const defaultAddress = {
	address: 'Some street',
	postcode: '',
	state: 'CA',
	country: 'US',
	phone: '123',
};

function createGetStateFn( newProps = { origin: {}, destination: {}, packages: {} } ) {
	const defaultUserMeta = {
		last_box_id: 'test_last_box',
	};
	const defaultRates = {
		values: {},
	};
	const defaultProps = {
		ignoreValidation: false,
		selectNormalized: false,
		isNormalized: false, // hack to prevent getLabelRates() in openPrintingFlow
		normalized: '',
		expanded: true, // hack to prevent getLabelRates() in openPrintingFlow
		values: defaultAddress,
	};
	const defaultPackages = {
		selected: {
			'default_box': {
				box_id: 'not_selected',
			}
		},
	};

	const origin = Object.assign( {}, defaultProps, newProps.origin );
	const destination = Object.assign( {}, defaultProps, newProps.destination );
	const packages = Object.assign( {}, defaultPackages, newProps.packages );
	const rates = Object.assign( {}, defaultRates, newProps.rates );
	const userMeta = Object.assign( {}, defaultUserMeta, newProps.userMeta );

	return function() {
		return {
			extensions: {
				woocommerce: {
					woocommerceServices: {
						[ siteId ]: {
							shippingLabel: {
								[ orderId ]: {
									form: {
										origin,
										destination,
										packages,
										rates,
									},
									openedPackageId: 'default_box',
								},
							},
							labelSettings: {
								meta: {
									user: userMeta,
								},
							},
						},
					},
				},
			},
		};
	};
}

// The address normalization request goes to the store's own REST route through
// `client/api/request`, which is what the plugin ships. It used to be mocked as a
// WordPress.com proxy call (`public-api.wordpress.com/rest/v1.1/jetpack-blogs/...`), a
// Calypso transport this plugin never loads - jest resolved the real Calypso module where
// webpack resolves a stub, so the suite exercised a request path that does not exist in
// the built bundle. The base URL matches the one seeded in tests/client/setup-test-framework.js.
const mockNormalizationRequest = ( valid = true ) => {
	// Several describe bodies call openPrintingFlow() directly, so requests are in flight
	// while later tests register their own interceptors. Replace whatever is registered and
	// keep the interceptor alive, so the outcome does not depend on which request lands first.
	nock.cleanAll();

	return nock( 'https://example.com' )
		.persist()
		.post( '/wp-json/wc/v1/connect/normalize-address' )
		.reply(
			200,
			valid
				? { success: true, normalized: true, is_trivial_normalization: true }
				: { success: false, message: 'Invalid address' }
		);
};

describe( 'Shipping label Actions', () => {
	describe( '#openPrintingFlow', () => {
		mockNormalizationRequest( true );

		describe( 'origin validation ignored', () => {
			const dispatchSpy = sinon.spy();

			openPrintingFlow( orderId, siteId )(
				dispatchSpy,
				createGetStateFn( { origin: { ignoreValidation: true } } )
			);

			it( 'toggle origin', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'origin',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( true );
			} );
			it( 'do not toggle destination', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'destination',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( false );
			} );
			it( 'open printing flow', () => {
				expect(
					dispatchSpy.calledWith( {
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
						orderId,
						siteId,
					} )
				).to.equal( true );
			} );
		} );

		describe( 'origin errors exist', () => {
			const dispatchSpy = sinon.spy();

			const errorStub = sinon.stub( selectors, 'getFormErrors' ).returns( { origin: true } );

			openPrintingFlow( orderId, siteId )( dispatchSpy, createGetStateFn() );

			it( 'toggles origin', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'origin',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( true );
			} );
			it( 'do not toggle destination', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'destination',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( false );
			} );
			it( 'open printing flow', () => {
				expect(
					dispatchSpy.calledWith( {
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
						orderId,
						siteId,
					} )
				).to.equal( true );
			} );

			errorStub.restore();
		} );

		describe( 'destination validation ignored', () => {
			const dispatchSpy = sinon.spy();

			openPrintingFlow( orderId, siteId )(
					dispatchSpy,
					createGetStateFn( {
						destination: { ignoreValidation: true },
					} )
			);

			it( 'toggle destination', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'destination',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( true );
			} );
			it( 'do not toggle origin', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'origin',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( false );
			} );
			it( 'open printing flow', () => {
				expect(
					dispatchSpy.calledWith( {
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
						orderId,
						siteId,
					} )
				).to.equal( true );
			} );
		} );

		describe( 'destination errors exist', () => {
			const dispatchSpy = sinon.spy();

			const errorStub = sinon.stub( selectors, 'getFormErrors' ).returns( { destination: true } );

			openPrintingFlow( orderId, siteId )( dispatchSpy, createGetStateFn() );

			it( 'toggle destination', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'destination',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( true );
			} );
			it( 'do not toggle origin', () => {
				expect(
					dispatchSpy.calledWith( {
						stepName: 'origin',
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( false );
			} );
			it( 'open printing flow', () => {
				expect(
					dispatchSpy.calledWith( {
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_OPEN_PRINTING_FLOW,
						orderId,
						siteId,
					} )
				).to.equal( true );
			} );
			errorStub.restore();
		} );

		nock.cleanAll();
	} );

	describe( '#convertToApiPackage', () => {
		it( 'totals value correctly (by quantity)', () => {
			const pckg = {
				id: 'id',
				box_id: 'box_id',
				service_id: 'service_id',
				length: 5,
				width: 6,
				height: 7,
				weight: 8,
				signature: 'signature',
				items: [
					{
						product_id: 123,
						quantity: 2,
					},
				],
			};

			const customsItems = {
				123: {
					weight: 4,
					value: 3,
					description: 'Product',
					tariffNumber: '098',
					originCountry: 'US',
				},
			};

			expect( convertToApiPackage( pckg, customsItems ) ).to.deep.equal( {
				id: 'id',
				box_id: 'box_id',
				service_id: 'service_id',
				length: 5,
				width: 6,
				height: 7,
				weight: 8,
				signature: 'signature',
				contents_type: 'merchandise',
				restriction_type: 'none',
				non_delivery_option: 'return',
				itn: '',
				items: [
					{
						product_id: 123,
						description: 'Product',
						quantity: 2,
						value: 6,
						weight: 8,
						hs_tariff_number: '098',
						origin_country: 'US',
					},
				],
			} );
		} );
	} );

	describe( '#submitAddressForNormalization', () => {
		/**
		 * `values` and `normalized` contain the same address.
		 *
		 * During the initial `submitAddressForNormalization` call,
		 * the function will still make a request because `isNormalized`
		 * is set to false, and blindly call the success callback afterwards,
		 * assuming that `normalizeAddress` has already changed the flag.
		 */
		const getState = createGetStateFn( {
			destination: {
				values: defaultAddress,
				normalized: defaultAddress,
			},
		} );

		it( 'Verifying a valid address proceeds to the next step', () => {
			const dispatchSpy = sinon.spy();

			// Mock a successful response
			mockNormalizationRequest( true );

			return submitAddressForNormalization( orderId, siteId, 'destination' )(
				dispatchSpy,
				getState
			).then( () => {
				expect(
					dispatchSpy.calledWith( {
						type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_TOGGLE_STEP,
						stepName: 'destination',
						orderId,
						siteId,
						expanded: null
					} )
				).to.equal( true );
			} );
		} );

		it( 'Validation request failure returns a false promise', () => {
			// Mock an unsuccessful response
			mockNormalizationRequest( false );

			return new Promise( resolve => {
				submitAddressForNormalization( orderId, siteId, 'destination' )( () => {}, getState ).catch(
					resolve
				);
			} );
		} );
	} );

	describe( '#confirmAddressSuggestion', () => {
		it( 'dispatches the correct action', () => {
			const dispatchSpy = sinon.spy();
			const group = 'destination';

			confirmAddressSuggestion( orderId, siteId, group )( dispatchSpy, createGetStateFn() );

			expect(
				dispatchSpy.calledWith( {
					type: WOOCOMMERCE_SERVICES_SHIPPING_LABEL_CONFIRM_ADDRESS_SUGGESTION,
					orderId,
					siteId,
					group,
				} )
			).to.equal( true );
		} );
	} );

	describe( 'getDefaultBoxSelection', () => {
		it( 'defaults to last used by user', () => {
			const { packageId, boxId } = getDefaultBoxSelection( orderId, siteId, createGetStateFn() );

			expect(
				packageId
			).to.equal( 'default_box' );
			expect(
				boxId
			).to.equal( 'test_last_box' );
		} );

		it( 'no default if package has been selected', () => {
			const { packageId, boxId } = getDefaultBoxSelection( orderId, siteId, createGetStateFn( {
				packages: {
					selected: {
						'default_box': {
							box_id: 'something_selected',
						},
					},
				},
			} ) ) || {};

			expect(
				packageId
			).to.equal( undefined );
			expect(
				boxId
			).to.equal( undefined );
		} );
	} );

	describe( 'getDefaultServiceSelection', () => {
		const lastUsedService = {
			last_service_id: 'Priority',
			last_carrier_id: 'usps',
		};

		// The rates a domestic shipment comes back with, which carry the service the user
		// bought last time.
		const domesticRates = {
			available: {
				default_box: {
					default: {
						rates: [
							{ service_id: 'Priority', carrier_id: 'usps', rate_id: 'rate_a', shipment_id: 'shp_1' },
							{ service_id: 'Express', carrier_id: 'usps', rate_id: 'rate_b', shipment_id: 'shp_1' },
						],
					},
				},
			},
			values: { default_box: '' },
		};

		// An international shipment is offered a different set of services, none of which
		// is the one the user bought last time.
		const internationalRates = {
			available: {
				default_box: {
					default: {
						rates: [
							{ service_id: 'PriorityMailInternational', carrier_id: 'usps', rate_id: 'rate_c', shipment_id: 'shp_2' },
							{ service_id: 'PriorityMailExpressInternational', carrier_id: 'usps', rate_id: 'rate_d', shipment_id: 'shp_2' },
						],
					},
				},
			},
			values: { default_box: '' },
		};

		it( 'defaults to the last used service when the retrieved rates carry it', () => {
			const { packageId, serviceId, carrierId } = getDefaultServiceSelection( orderId, siteId, createGetStateFn( {
				userMeta: lastUsedService,
				rates: domesticRates,
			} ) ) || {};

			expect( packageId ).to.equal( 'default_box' );
			expect( serviceId ).to.equal( 'Priority' );
			expect( carrierId ).to.equal( 'usps' );
		} );

		it( 'no default when the retrieved rates do not carry the last used service', () => {
			const { packageId, serviceId, carrierId } = getDefaultServiceSelection( orderId, siteId, createGetStateFn( {
				userMeta: lastUsedService,
				rates: internationalRates,
			} ) ) || {};

			expect( packageId ).to.equal( undefined );
			expect( serviceId ).to.equal( undefined );
			expect( carrierId ).to.equal( undefined );
		} );

		it( 'no default when no rates have been retrieved yet', () => {
			const { serviceId } = getDefaultServiceSelection( orderId, siteId, createGetStateFn( {
				userMeta: lastUsedService,
			} ) ) || {};

			expect( serviceId ).to.equal( undefined );
		} );

		it( 'no default when the user has not bought a label before', () => {
			const { serviceId } = getDefaultServiceSelection( orderId, siteId, createGetStateFn( {
				rates: domesticRates,
			} ) ) || {};

			expect( serviceId ).to.equal( undefined );
		} );
	} );
} );
