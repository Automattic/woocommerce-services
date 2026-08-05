/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';

describe( 'StoreNotices', () => {
	let createNotice;
	let removeNotices;
	let StoreNotices;
	let existingNotices;

	const usCart = { shippingAddress: { country: 'US', state: 'CA', postcode: '94105' } };
	const caCart = { shippingAddress: { country: 'CA', state: 'ON', postcode: 'M5V 2T6' } };

	const zipNotices = {
		'woocommerce-services': {
			notices: [ { type: 'error', message: 'ZIP could not be validated.' } ],
		},
	};

	beforeEach( () => {
		jest.resetModules();

		createNotice = jest.fn();
		removeNotices = jest.fn();
		existingNotices = [];

		global.window.wp = {
			element: { useEffect: React.useEffect },
			data: {
				useDispatch: () => ( { createNotice, removeNotices } ),
				useSelect: ( selector ) => selector( () => ( { getNotices: () => existingNotices } ) ),
			},
		};

		( { StoreNotices } = require( '../store-notices' ) );
	} );

	afterEach( () => {
		delete global.window.wp;
	} );

	it( 'creates notices from the extension data for US addresses', () => {
		mount( <StoreNotices extensions={ zipNotices } cart={ usCart } /> );

		// The id prefix is the contract the removal path relies on to find our own
		// notices, so it is asserted rather than ignored.
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'ZIP could not be validated.',
			expect.objectContaining( { id: 'wcservices-store-notices-0', context: 'wc/cart' } )
		);
	} );

	it( 'does not create notices for non-US addresses', () => {
		mount( <StoreNotices extensions={ zipNotices } cart={ caCart } /> );

		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'removes only its own notices for US addresses', () => {
		existingNotices = [
			{ id: 'wcservices-store-notices-0' },
			{ id: 'some-other-plugin-notice' },
		];

		mount( <StoreNotices extensions={ {} } cart={ usCart } /> );

		expect( removeNotices ).toHaveBeenCalledWith( [ 'wcservices-store-notices-0' ], 'wc/cart' );
	} );

	it( 'does not remove notices for non-US addresses', () => {
		existingNotices = [ { id: 'wcservices-store-notices-0' } ];

		mount( <StoreNotices extensions={ {} } cart={ caCart } /> );

		expect( removeNotices ).not.toHaveBeenCalled();
	} );

	it( 'creates notices again when new extension data arrives', () => {
		const wrapper = mount( <StoreNotices extensions={ zipNotices } cart={ usCart } /> );

		wrapper.setProps( {
			extensions: {
				'woocommerce-services': {
					notices: [ { type: 'error', message: 'Address could not be validated.' } ],
				},
			},
		} );

		expect( createNotice ).toHaveBeenCalledTimes( 2 );
		expect( createNotice ).toHaveBeenLastCalledWith(
			'error',
			'Address could not be validated.',
			expect.objectContaining( { id: 'wcservices-store-notices-0', context: 'wc/cart' } )
		);
	} );

	it( 'renders without crashing when the extension data is missing', () => {
		// Old WooCommerce versions register the blocks integration but not the
		// Store API extension, so extensions has no woocommerce-services key.
		expect( () => {
			mount( <StoreNotices extensions={ {} } cart={ usCart } /> );
		} ).not.toThrow();

		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'renders without crashing when the notices key is missing', () => {
		expect( () => {
			mount( <StoreNotices extensions={ { 'woocommerce-services': {} } } cart={ usCart } /> );
		} ).not.toThrow();

		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'renders without crashing when the notices data is not a list', () => {
		// Anything on the Store API response is outside our control; a non-list must not
		// reach forEach and take down the checkout fill.
		expect( () => {
			mount(
				<StoreNotices
					extensions={ { 'woocommerce-services': { notices: {} } } }
					cart={ usCart }
				/>
			);
		} ).not.toThrow();

		expect( createNotice ).not.toHaveBeenCalled();
	} );
} );
