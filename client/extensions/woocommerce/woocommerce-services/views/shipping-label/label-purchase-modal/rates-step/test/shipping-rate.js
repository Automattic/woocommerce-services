/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, mount, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'
import { RadioControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ShippingRate from '../shipping-rate';
import CarrierLogo from '../carrier-logo';

configure({ adapter: new Adapter() });


const rateObject = {
	title: 'test title',
	service_id: 'service_1',
	carrier_id: 'usps',
	rate: '10',
	delivery_days: '2',
	delivery_date_guaranteed: true,
	delivery_date: new Date(2020, 1, 1)
};

const signatureRates = {
	rate1: {
		optionNetCost: 3,
		label: 'signature rate 1'
	}
};

const props = {
	rateObject,
	signatureRates
};

const shippingRateWrapper = mount( <ShippingRate { ...props } /> );

describe( 'ShippingRate', () => {

	it( 'renders a radio control', function() {
		expect( shippingRateWrapper.find( RadioControl ) ).to.have.lengthOf( 1 );
	} );

	it( "renders the carrier's logo", () => {
		expect( shippingRateWrapper.find( CarrierLogo ) ).to.have.lengthOf( 1 );
	} );

	it( "renders the rate title", () => {
		const title = <div className="rates-step__shipping-rate-description-title">test title</div>;
		expect( shippingRateWrapper.contains( title ) ).to.equal( true );
	} );

	it( "renders tracking information", () => {
		expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( /Includes USPS tracking/ );
	} );

	it( "renders the signature options", () => {
		expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).contains( SelectControl ) ).to.equal( true );
	} );

	it( "renders the rate's amount", () => {
		expect( shippingRateWrapper.contains( <div className="rates-step__shipping-rate-rate">$10.00</div> ) ).to.equal( true );
	} );

	it( "renders the delivery date", () => {
		expect( shippingRateWrapper.contains( <div className="rates-step__shipping-rate-delivery-date">February 1</div> ) ).to.equal( true );
	} );
}
);
