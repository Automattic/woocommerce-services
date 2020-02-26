/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'
import { moment, translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { ShippingLabelViewWrapper } from '../view-wrapper-label';

configure( { adapter: new Adapter() } );

global.wcConnectData = {
	nonce: 1,
	baseURL: 'http://localhost/',
	wcs_server_connection: 1,
};

const labelEventsTwoPurchased = [
	{
		type: 'LABEL_PURCHASED',
		labelIndex: 1,
		productNames: [ '' ],
		createdDate: 1582613177000, //Feb. 24, 2020
	},
	{
		type: 'LABEL_PURCHASED',
		labelIndex: 2,
		productNames: [ '' ],
		createdDate: 1551113177000,
	}
];

function createViewWrapperLabelWrapper( {
	loaded,
	labelsEnabled,
	items,
	events,
	order
} ) {

	const props = {
		orderId: 1000,
		siteId: 10,
		loaded: loaded || true,
		labelsEnabled: labelsEnabled || true,
		items: items || 1,
		events: events || {},
		fetchOrder: () => order || {},
		moment,
		translate,
	};

	return shallow( <ShippingLabelViewWrapper { ...props } /> );
}

describe( 'ShippingLabelViewWrapper', () => {

	describe( 'for 1 item unfulfilled', () => {
		const viewWrapperLabel = createViewWrapperLabelWrapper( {} );

		it( 'renders singular ready to to be fulfilled message', function() {
			expect( viewWrapperLabel.find( '.shipping-label__banner-fulfilled-message' ) ).to.contain.text( '1 item is ready' );
		} );
	} );

	describe( 'for 2 items unfulfilled', () => {
		const viewWrapperLabel = createViewWrapperLabelWrapper( { items: 2 } );

		it( 'renders plural ready to to be fulfilled message', function() {
			expect( viewWrapperLabel.find( '.shipping-label__banner-fulfilled-message' ) ).to.contain.text( '2 items are ready' );
		} );
	} );

	describe( 'for 1 item fulfilled', () => {
		const viewWrapperLabel = createViewWrapperLabelWrapper( { events: labelEventsTwoPurchased } );

		it( 'renders singular fulfilled message', function() {
			expect( viewWrapperLabel.find( '.shipping-label__banner-fulfilled-message' ) ).to.contain.text( '1 item was fulfilled on Feb 24, 2020' );
		} );
	} );

	describe( 'for 2 items fulfilled', () => {
		const viewWrapperLabel = createViewWrapperLabelWrapper( { items:2, events: labelEventsTwoPurchased } );

		it( 'renders singular fulfilled message', function() {
			expect( viewWrapperLabel.find( '.shipping-label__banner-fulfilled-message' ) ).to.contain.text( '2 items were fulfilled on Feb 24, 2020' );
		} );
	} );
} );
