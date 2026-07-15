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

	const usCart = { shippingAddress: { country: 'US', state: 'CA', postcode: '94105' } };

	beforeEach( () => {
		jest.resetModules();

		createNotice = jest.fn();
		removeNotices = jest.fn();

		global.window.wp = {
			element: { useEffect: React.useEffect },
			data: {
				useDispatch: () => ( { createNotice, removeNotices } ),
				useSelect: () => [],
			},
		};

		( { StoreNotices } = require( '../store-notices' ) );
	} );

	afterEach( () => {
		delete global.window.wp;
	} );

	it( 'creates notices from the extension data for US addresses', () => {
		const extensions = {
			'woocommerce-services': {
				notices: [ { type: 'error', message: 'ZIP could not be validated.' } ],
			},
		};

		mount( <StoreNotices extensions={ extensions } cart={ usCart } /> );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'ZIP could not be validated.',
			expect.objectContaining( { context: 'wc/cart' } )
		);
	} );

	it( 'does not create notices for non-US addresses', () => {
		const extensions = {
			'woocommerce-services': {
				notices: [ { type: 'error', message: 'ZIP could not be validated.' } ],
			},
		};
		const caCart = { shippingAddress: { country: 'CA', state: 'ON', postcode: 'M5V 2T6' } };

		mount( <StoreNotices extensions={ extensions } cart={ caCart } /> );

		expect( createNotice ).not.toHaveBeenCalled();
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
} );
