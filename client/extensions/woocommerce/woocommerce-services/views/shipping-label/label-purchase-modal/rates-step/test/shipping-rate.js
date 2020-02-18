/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, mount } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ShippingRate from '../shipping-rate';
import CarrierLogo from '../carrier-logo';

configure({ adapter: new Adapter() });

function createShippingRateWrapper( {
	rateId,
	title,
	serviceId,
	carrierId,
	rateAmount,
	deliveryDays,
	deliveryDateGuaranteed,
	deliveryDate,
	tracking,
	insuranceAmount,
	freePickup,
	signatureRequired,
	activeRateId
} ) {

	const props = {
		rateObject: {
			rate_id: rateId || 'rate_1',
			title: title || 'test title',
			service_id: serviceId || 'service_1',
			carrier_id: carrierId || 'random carrier',
			rate: rateAmount || '10',
			delivery_days: deliveryDays || '2',
			delivery_date_guaranteed: deliveryDateGuaranteed || true,
			delivery_date: deliveryDate || new Date(2020, 1, 1),
		},
		signatureRates: {
			rate1: {
				optionNetCost: 3,
				label: 'signature rate 1'
			}
		},
		includedServices: {
			tracking: tracking || true,
			insurance: insuranceAmount || 100,
			free_pickup: freePickup || true,
			signature_required: signatureRequired || false,
		},
		activeRateId
	};

	return mount( <ShippingRate { ...props } /> );
}

describe( 'ShippingRate', () => {

	describe( 'for all rates', () => {

		const shippingRateWrapper = createShippingRateWrapper( {} );

		it( 'renders a radio control', function() {
			expect( shippingRateWrapper.find( RadioControl ) ).to.have.lengthOf( 1 );
		} );

		it( "renders the carrier's logo", () => {
			expect( shippingRateWrapper.find( CarrierLogo ) ).to.have.lengthOf( 1 );
		} );

		it( 'renders the rate title', () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-description-title">test title</div> );
		} );

		it( "renders the rate's amount", () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-rate">$10.00</div> );
		} );

		it( 'renders the delivery date', () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-delivery-date">February 1</div> );
		} );

	} );


	describe( 'for regular carriers', () => {

		const shippingRateWrapper = createShippingRateWrapper( { carrierId: 'not usps' } );

		it( 'displays included tracking in the list of services', () => {
			const listOfServices = /Includes tracking, Insurance \(up to \$100.00\), Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );
	});

	describe( 'for USPS carriers', () => {

		const shippingRateWrapper = createShippingRateWrapper( { carrierId: 'usps' } );

		it( 'displays USPS included tracking in the list of services', () => {
			const listOfServices = /Includes USPS tracking, Insurance \(up to \$100.00\), Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

	});

	describe( 'for rates with signature required', () => {

		const shippingRateWrapper = createShippingRateWrapper( { signatureRequired: true } );

		it( 'renders an abreviated list of services, including "Signature required"', () => {
			const listOfServices = /Includes tracking, Insurance \(up to \$100.00\), Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

	} );

	describe( 'when has focus', () => {

		const activeShippingRateWrapper = createShippingRateWrapper( { rateId: 'rate_1', activeRateId: 'rate_1', signatureRequired: true} );
		const inactiveShippingRateWrapper = createShippingRateWrapper( { rateId: 'rate_2', activeRateId: 'rate_1'} );

		it( 'displays the signatures section', () => {

			expect( activeShippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ) ).to.have.descendants( '.rates-step__shipping-rate-description-signature-select' );
			expect( activeShippingRateWrapper.find( '.rates-step__shipping-rate-description-signature-select option' ).at( 0 ) ).to.have.text( 'Signature required (Free)' );
			expect( inactiveShippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ) ).to.not.have.descendants( '.rates-step__shipping-rate-description-signature-select' );

		} );

	} );
}
);
