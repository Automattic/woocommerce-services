/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';

/**
 * Internal dependencies
 */
import { getRatesErrors, getRawAddressErrors, getTotalPriceBreakdown, isAddressUsable } from '../selectors';

describe( '#getRatesErrors', () => {
	// when there are selected rates
	// the values match `service_id` in the rate object
	const firstBoxSelectedValues = {
		serviceId: 'Priority', signatureRequired: false,
	};
	const secondBoxSelectedValues = {
		serviceId: 'Express', signatureRequired: false,
	};

	// rates when there are no server errors, for each box
	const firstBoxRatesWithNoServerErrors = {
		box_1: {
			default: {
				shipment_id: 'shp_1',
				rates: [
					{
						rate_id: 'rate_box_1_a',
						service_id: 'Priority',
						carrier_id: 'usps',
						title: 'USPS - Priority Mail',
						rate: 10,
						is_selected: true,
					},
					{
						rate_id: 'rate_box_1_b',
						service_id: 'Express',
						carrier_id: 'usps',
						title: 'USPS - Express Mail',
						rate: 11,
						is_selected: false,
					},
				],
				errors: [],
			}
		},
	};
	const secondBoxRatesWithNoServerErrors = {
		box_2: {
			default: {
				shipment_id: 'shp_2',
				rates: [
					{
						rate_id: 'rate_box_2_a',
						service_id: 'Priority',
						carrier_id: 'usps',
						title: 'USPS - Priority Mail',
						rate: 20,
						is_selected: false,
					},
					{
						rate_id: 'rate_box_2_b',
						service_id: 'Express',
						carrier_id: 'usps',
						title: 'USPS - Express Mail',
						rate: 21,
						is_selected: true,
					},
				],
				errors: [],
			}
		},
	};

	// rates when there are server errors
	const firstBoxRatesWithError = {
		box_1: {
			default: {
				rates: [],
				errors: [
					{
						code: 'ERROR 1',
						message: 'There was an error!',
					},
				],
			}
		},
	};
	const firstBoxRatesWithUserMessageError = {
		box_1: {
			default: {
				rates: [],
				errors: [
					{
						code: 'ERROR 1',
						userMessage: 'This is a friendly error message!',
					},
				],
			}
		},
	};
	const firstBoxRatesWithErrorButNoErrorMessage = {
		box_1: {
			default: {
				rates: [],
				errors: [
					{
						code: 'ERROR 1',
					},
				],
			}
		},
	};
	const firstBoxRatesWithMultipleErrors = {
		box_1: {
			default: {
				rates: [],
				errors: [
					{
						code: 'ERROR A',
						message: 'Error 1',
					},
					{
						code: 'ERROR B',
						message: 'Error 2',
					},
				],
			}
		},
	};

	describe( 'a box with server errors', () => {
		// i.e. it has an errors array, and an empty rates array

		// `rates` is form.rates in the state
		// `values` is the currently-selected rate in the form
		// an empty string means none is selected
		// `available` is all the available rates for the package/box

		// rates with a message prop in the error object
		const rates = {
			values: {
				box_1: { serviceId: '', signatureRequired: false }
			},
			available: firstBoxRatesWithError,
		};
		const result = getRatesErrors( rates );

		it( 'should return the server error', () => {
			expect( result ).to.eql( {
				box_1: [ 'There was an error!' ],
			} );
		} );

		// rates with a userMessage prop in the error object
		const ratesWithUserMessage = {
			...rates,
			available: firstBoxRatesWithUserMessageError,
		};
		const resultWithUserMessage = getRatesErrors( ratesWithUserMessage );

		it( 'should return the `userMessage` if it exists', () => {
			expect( resultWithUserMessage ).to.eql( {
				box_1: [ 'This is a friendly error message!' ],
			} );
		} );

		// rates with no message or userMessage prop in the error object
		const ratesWithNoMessage = {
			...rates,
			available: firstBoxRatesWithErrorButNoErrorMessage,
		};
		const resultWithErrorAndNoMessage = getRatesErrors( ratesWithNoMessage );

		it( 'should return the the default error message if no error message sent', () => {
			expect( resultWithErrorAndNoMessage ).to.eql( {
				box_1: [ "We couldn't get a rate for this package, please try again." ],
			} );
		} );

		describe( 'multiple errors', () => {
			const ratesWithMultipleErrors = {
				values: {
					box_1: { serviceId: '', signatureRequired: false }
				},
				available: firstBoxRatesWithMultipleErrors,
			};
			const resultWithMultipleErrors = getRatesErrors( ratesWithMultipleErrors );

			it( 'should return all the errors', () => {
				expect( resultWithMultipleErrors ).to.eql( {
					box_1: [ 'Error 1', 'Error 2' ],
				} );
			} );
		} );
	} );

	describe( 'a box with no server errors', () => {
		// i.e. it has a rates array, and an empty errors array

		describe( 'rate is selected', () => {
			const rates = {
				values: {
					box_1: Object.assign( {}, firstBoxSelectedValues ),
				},
				available: firstBoxRatesWithNoServerErrors,
			};
			const result = getRatesErrors( rates );

			it( 'should return no error', () => {
				expect( result ).to.eql( {
					box_1: [],
				} );
			} );
		} );

		describe( 'rate is not selected', () => {
			const rates = {
				values: {
					box_1: { serviceId: '', signatureRequired: false }
				},
				available: firstBoxRatesWithNoServerErrors,
			};
			const result = getRatesErrors( rates );

			it( 'should return an error', () => {
				expect( result ).to.eql( {
					box_1: [ 'Please choose a rate' ],
				} );
			} );
		} );
	} );

	describe( 'two boxes: box 1 with server errors, box 2 without', () => {
		describe( 'rate is selected for box 2', () => {
			const rates = {
				values: {
					box_1: { serviceId: '', signatureRequired: false },
					box_2: Object.assign( {}, secondBoxSelectedValues ),
				},
				available: Object.assign( {}, firstBoxRatesWithError, secondBoxRatesWithNoServerErrors ),
			};
			const result = getRatesErrors( rates );

			it( 'should return an error only for the first box', () => {
				expect( result ).to.eql( {
					box_1: [ 'There was an error!' ],
					box_2: [],
				} );
			} );
		} );

		describe( 'rate not selected for box 2', () => {
			const rates = {
				values: {
					box_1: { serviceId: '', signatureRequired: false },
					box_2: { serviceId: '', signatureRequired: false }
				},
				available: Object.assign( {}, firstBoxRatesWithError, secondBoxRatesWithNoServerErrors ),
			};
			const result = getRatesErrors( rates );

			it( 'should return the server error for the first box and the form error for the second', () => {
				expect( result ).to.eql( {
					box_1: [ 'There was an error!' ],
					box_2: [ 'Please choose a rate' ],
				} );
			} );
		} );
	} );
} );

describe( 'Shipping label selectors', () => {
	const siteId = 1;
	const orderId = 1;
	const getFullState = labelsState => {
		const countries = [
			{
				code: 'US',
				name: 'United States',
				states: [
					{
						code: 'CA',
						name: 'California',
					},
				],
			},
		];

		return {
			extensions: {
				woocommerce: {
					woocommerceServices: {
						[ siteId ]: {
							shippingLabel: {
								[ orderId ]: labelsState,
							},
						},
					},
					sites: {
						[ siteId ]: {
							data: {
								locations: [
									{
										code: 'NA',
										name: 'North America',
										countries,
									},
								],
							},
						},
					},
				},
			},
		};
	};

	it( 'getTotalPriceBreakdown - returns null if the form is not loaded', () => {
		const state = getFullState( { loaded: false } );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result ).to.eql( null );
	} );

	it( 'getTotalPriceBreakdown - returns null when there are no packages and no rates available', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {},
					available: {},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result ).to.eql( null );
	} );

	it( 'getTotalPriceBreakdown - returns null when there are no rates available', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {
						default_box: { serviceId: '', signatureRequired: false },
					},
					available: {},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result ).to.eql( null );
	} );

	it( 'getTotalPriceBreakdown - returns null when there are no rates selected', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {
						default_box: { serviceId: '', signatureRequired: false },
					},
					available: {
						default_box: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 6.61,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfa',
										retail_rate: 7.8,
										service_id: 'Priority',
										title: 'USPS - Priority Mail',
									},
								],
							}
						},
					},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result ).to.eql( null );
	} );

	it( 'getTotalPriceBreakdown - returns a breakdown when a rate is selected', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {
						default_box: { serviceId: 'Priority', signatureRequired: false }
					},
					available: {
						default_box: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 6.61,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfa',
										retail_rate: 7.8,
										service_id: 'Priority',
										title: 'USPS - Priority Mail',
									},
								],
							}
						},
					},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result.prices ).to.eql( [ { title: 'USPS - Priority Mail', carrierId: 'usps', carrierTitle: 'USPS', retailRate: 7.8, rateWithDiscount: 6.61, addons: [] } ] );
		expect( result.discount ).to.eql( 1.19 );
		expect( result.total ).to.eql( 6.61 );
	} );

	it( 'getTotalPriceBreakdown - returns a breakdown when one out of many rates is selected', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {
						box1: { serviceId: 'Priority', signatureRequired: false },
						box2: { serviceId: '', signatureRequired: false },
					},
					available: {
						box1: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 6.61,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfa',
										retail_rate: 7.8,
										service_id: 'Priority',
										title: 'USPS - Priority Mail',
									},
								],
							}
						},
						box2: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 21.18,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfb',
										retail_rate: 23.85,
										service_id: 'Express',
										title: 'USPS - Express Mail',
									},
								],
							}
						},
					},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result.prices ).to.eql( [ { title: 'USPS - Priority Mail', carrierId: 'usps', carrierTitle: 'USPS', retailRate: 7.8, rateWithDiscount: 6.61, addons: [] } ] );
		expect( result.discount ).to.eql( 1.19 );
		expect( result.total ).to.eql( 6.61 );
	} );

	it( 'getTotalPriceBreakdown - returns a breakdown when many rates are selected', () => {
		const state = getFullState( {
			form: {
				rates: {
					values: {
						box1: { serviceId: 'Priority', signatureRequired: false },
						box2: { serviceId: 'Express', signatureRequired: false }
					},
					available: {
						box1: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 6.61,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfa',
										retail_rate: 7.8,
										service_id: 'Priority',
										title: 'USPS - Priority Mail',
									},
								],
							}
						},
						box2: {
							default: {
								rates: [
									{
										carrier_id: 'usps',
										is_selected: false,
										rate: 21.18,
										rate_id: 'rate_f2afdd2045d84b8394a47730cf264bfb',
										retail_rate: 23.85,
										service_id: 'Express',
										title: 'USPS - Express Mail',
									},
								],
							}
						},
					},
				},
			},
		} );
		const result = getTotalPriceBreakdown( state, orderId, siteId );
		expect( result.prices ).to.eql( [

			{ title: 'USPS - Priority Mail', carrierId: 'usps', carrierTitle: 'USPS', retailRate: 7.8, rateWithDiscount: 6.61, addons: [] },
			{ title: 'USPS - Express Mail', carrierId: 'usps', carrierTitle: 'USPS', retailRate: 23.85, rateWithDiscount: 21.18, addons: [] },
		] );
		expect( result.discount ).to.eql( 3.86 );
		expect( result.total ).to.eql( 27.79 );
	} );

	const getAddressState = ( group, values = {} ) => {
		return getFullState( {
			form: {
				[ group ]: {
					values: {
						company: 'Automaggic',
						address_2: '',
						city: 'Cupertino',
						state: 'CA',
						postcode: '95014-0642',
						country: 'US',
						phone: '',
						name: 'Peter Anderson',
						address: 'Apple Park Way1',
						...values,
					},
				},
			},
		} );
	};

	it( 'isAddressUsable - returns no errors with correct data', () => {
		const group = 'destination';
		const state = getAddressState( group );

		const result = isAddressUsable( state, orderId, group, siteId );
		expect( result ).to.equal( true );
	} );

	const incorrectAddressValues = [
		// name        value         shouldFail
		[ 'company', '', false ],
		[ 'address', '', true ],
		[ 'address_2', '', false ],
		[ 'city', '', true ],
		[ 'postcode', '95014-064', true ],
		[ 'postcode', '', true ],
		[ 'country', '', true ],
		[ 'country', 'Wonderland', false ],
		[ 'phone', '', false ],
		[ 'name', '', true ],
		[ 'state', '', true ],

		// States are selected in a dropdown and individual values are not validated
		[ 'state', 'QQ', false ],
	];

	incorrectAddressValues.forEach( row => {
		const [ name, value, shouldFail ] = row;

		const verb = shouldFail ? 'triggers' : 'does not trigger';
		const description = `isAddressUsable - ${ verb } an error with incorrect ${ name }`;
		it( description, () => {
			const group = 'destination';
			const state = getAddressState( group, {
				[ name ]: value,
			} );

			const result = isAddressUsable( state, orderId, group, siteId );
			expect( result ).to.equal( ! shouldFail );
		} );
	} );
} );

describe( 'Shipping label form validation', () => {
	const siteId = 1;
	const addressData = {
		values: {
			company: 'Automaggic',
			address_2: '',
			city: 'Cupertino',
			state: 'CA',
			postcode: '95014-0642',
			country: 'US',
			phone: '',
			name: 'Peter Anderson',
			address: 'Apple Park Way1',
		}
	};
	const appState = {};

	it ( 'with no destination phone entered and destination phone required, return error', () => {
		const fieldsToValidate = { destinationPhone: true };
		const errors = getRawAddressErrors( appState, addressData, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.have.property( 'phone' );
	} );

	it ( 'with no destination phone entered and destination phone not required, do not return error', () => {
		const fieldsToValidate = {};
		const errors = getRawAddressErrors( appState, addressData, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.not.have.property( 'phone' );
	} );

	it ( 'with no origin phone entered and origin phone required, return error', () => {
		const fieldsToValidate = { originPhone: true };
		const errors = getRawAddressErrors( appState, addressData, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.have.property( 'phone' );
	} );

	it ( 'with no origin phone entered and origin phone not required, do not return error', () => {
		const fieldsToValidate = { };
		const errors = getRawAddressErrors( appState, addressData, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.not.have.property( 'phone' );
	} );

	it ( 'with origin phone entered and origin phone required, do not return error', () => {
		const fieldsToValidate = { originPhone: true };
		const addressDataWithPhone = Object.assign( {}, addressData);
		addressDataWithPhone.values.phone = '5551235667';
		const errors = getRawAddressErrors( appState, addressDataWithPhone, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.not.have.property( 'phone' );
	} );

	it ( 'with destination phone entered and destination phone required, do not return error', () => {
		const fieldsToValidate = { destinationPhone: true };
		const addressDataWithPhone = Object.assign( {}, addressData );
		addressDataWithPhone.values.phone = '5551235667';
		const errors = getRawAddressErrors( appState, addressDataWithPhone, siteId, fieldsToValidate );
		expect( errors ).to.be.an( 'Object' ).and.to.not.have.property( 'phone' );
	} );

} );
