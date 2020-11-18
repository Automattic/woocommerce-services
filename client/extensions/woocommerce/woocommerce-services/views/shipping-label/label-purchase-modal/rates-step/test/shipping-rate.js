/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, mount } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'
import { CheckboxControl, RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ShippingRate from '../shipping-rate';
import CarrierIcon from 'woocommerce/woocommerce-services/components/carrier-icon';

configure({ adapter: new Adapter() });

const signatureRequiredRate = {
	optionNetCost: 0,
	label: 'Signature required'
};

const adultSignatureRequiredRate = {
	optionNetCost: 3,
	label: 'Adult Signature required'
};

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
	signatureRates,
	isSelected,
} ) {

	const props = {
		orderId: 1000,
		siteId: 10,
		rateObject: {
			rate_id: rateId || 'rate_1',
			title: title || 'test title',
			service_id: serviceId || 'service_1',
			carrier_id: carrierId || 'random carrier',
			rate: rateAmount || 10,
			delivery_days: deliveryDays || 2,
			delivery_date_guaranteed: deliveryDateGuaranteed || true,
			delivery_date: deliveryDate || "2020-01-01T23:59:00.000Z",
			tracking: tracking || true,
			insurance: insuranceAmount === undefined ? 100 : insuranceAmount,
			free_pickup: freePickup || true,
		},
		signatureRates: signatureRates || {
			rate1: signatureRequiredRate,
			rate2: adultSignatureRequiredRate
		},
		updateValue() {},
		isSelected: isSelected || true
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
			expect( shippingRateWrapper.find( CarrierIcon ) ).to.have.lengthOf( 1 );
		} );

		it( 'renders the rate title', () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-description-title">test title</div> ); // eslint-disable-line
		} );

		it( "renders the rate's amount", () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-rate">$10.00</div> ); // eslint-disable-line

		} );

		it( 'renders the delivery date', () => {
			expect( shippingRateWrapper ).to.contain( <div className="rates-step__shipping-rate-delivery-date">January 1</div> ); // eslint-disable-line
		} );

	} );


	describe( 'for regular carriers', () => {

		const shippingRateWrapper = createShippingRateWrapper( { carrierId: 'not usps', signatureRates: { rate2: adultSignatureRequiredRate } } );

		it( 'displays included tracking in the list of services', () => {
			const listOfServices = /Includes tracking, Insurance \(up to \$100.00\), Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );
	});

	describe( 'for USPS carriers', () => {

		const shippingRateWrapper = createShippingRateWrapper( { carrierId: 'usps', signatureRates: { rate2: adultSignatureRequiredRate } } );

		it( 'displays USPS included tracking in the list of services', () => {
			const listOfServices = /Includes USPS tracking, Insurance \(up to \$100.00\), Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

	});

	describe( 'for rates with signature required', () => {

		const shippingRateWrapper = createShippingRateWrapper( { signatureRates: { rate1: signatureRequiredRate } } );

		it( 'renders an abreviated list of services, including "Signature required"', () => {
			const listOfServices = /Includes tracking, Insurance \(up to \$100.00\), Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

	} );

	describe( 'for rates with insurance', () => {
		it( 'renders the string without additional dollar sign in front', () => {
			const shippingRateWrapper = createShippingRateWrapper( { insuranceAmount: 'limited', signatureRates: { rate1: signatureRequiredRate } } );
			const listOfServices = /Includes tracking, Insurance \(limited\), Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

		it( 'renders the number with additional dollar sign in front if insurance is a number-string', () => {
			const shippingRateWrapper = createShippingRateWrapper( { insuranceAmount: '100', signatureRates: { rate1: signatureRequiredRate } } );
			const listOfServices = /Includes tracking, Insurance \(up to \$100.00\), Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

		it( 'renders the number with additional dollar sign in front if insurance is a number', () => {
			const shippingRateWrapper = createShippingRateWrapper( { insuranceAmount: 234.99, signatureRates: { rate1: signatureRequiredRate } } );
			const listOfServices = /Includes tracking, Insurance \(up to \$234.99\), Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

		it( 'should not include insurance if it is "0"', () => {
			const shippingRateWrapper = createShippingRateWrapper( { insuranceAmount: '0', signatureRates: { rate1: signatureRequiredRate } } );
			const listOfServices = /Includes tracking, Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

		it( 'should not include insurance if it is 0', () => {
			const shippingRateWrapper = createShippingRateWrapper( { insuranceAmount: 0, signatureRates: { rate1: signatureRequiredRate } } );
			const listOfServices = /Includes tracking, Signature required, Eligible for free pickup/;

			expect( shippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ).text() ).to.match( listOfServices );
		} );

	} );

	describe( 'for a list of rates', () => {

		const activeShippingRateWrapper = createShippingRateWrapper( { rateId: 'rate_1'} );
		const inactiveShippingRateWrapper = createShippingRateWrapper( { rateId: 'rate_2', isSelected: false} );

		describe( 'when one of them is selected', () => {

			activeShippingRateWrapper.simulate( 'click' );
			const shippingRateWrapperDetails = activeShippingRateWrapper.find( '.rates-step__shipping-rate-description-details' );
			const freeSignatureCheckbox = shippingRateWrapperDetails.find( CheckboxControl ).at( 0 );
			const adultSignatureCheckbox = shippingRateWrapperDetails.find( CheckboxControl ).at( 1 );

			it( 'the signatures section is displayed only for the selected rate', () => {
				expect( inactiveShippingRateWrapper.find( '.rates-step__shipping-rate-description-details' ) ).to.not.have.descendants( '.rates-step__shipping-rate-description-signature-select' );
				expect( freeSignatureCheckbox.prop( 'label' ) ).to.equal( 'Signature required (free)' );
				expect( adultSignatureCheckbox.prop( 'label' ) ).to.equal( 'Adult Signature required (+$3.00)' );
				expect( shippingRateWrapperDetails.find( CheckboxControl ) ).to.have.lengthOf( 2 );
			} );

			describe( 'the selected one checkboxes', () => {

				it( 'behave as radio buttons', () => {
					expect( activeShippingRateWrapper.state( 'selectedSignature' ) ).to.equal( undefined );
					freeSignatureCheckbox.prop( 'onChange' )( true );
					expect( activeShippingRateWrapper.state( 'selectedSignature' ) ).to.deep.equal( { id: 0, netCost: 0, value: "rate1" } );

					adultSignatureCheckbox.prop( 'onChange' )( true );
					expect ( activeShippingRateWrapper.state( 'selectedSignature' ) ).to.deep.equal( { id: 1, netCost: 3, value: "rate2" } );
				} );

				it( "add its amount to the rate's total amount", () => {
					adultSignatureCheckbox.prop( 'onChange' )( true );
					activeShippingRateWrapper.update();
					expect( activeShippingRateWrapper.find('.rates-step__shipping-rate-rate').text() ).to.equal( '$13.00' );
				} );

			} )

		} )

	} );
}
);
