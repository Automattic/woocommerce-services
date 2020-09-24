/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'

/**
 * Internal dependencies
 */
import { PurchaseSection } from '../index.js';
import PurchaseButton from '../purchase-button';
import CreditCardButton from '../credit-card-button';

configure({ adapter: new Adapter() });

function createPurchaseSectionWrapper( initProps = {} ) {
	const props = {
		orderId: 1000,
		siteId: 10,
		translate: ( text ) => text,
		errors: {},
		form: { 
			origin: { values: { country: 'US' } },
			rates: {},
		},
		hasLabelsPaymentMethod: true,
		paymentMethods: [ { card_digits: "4242", card_type: "visa", expiry: "2025-09-30", name: "Test", payment_method_id: 12345 } ],
		disablePurchase: false,
		...initProps,
	};

	const wrapper = shallow( <PurchaseSection { ...props } /> );

	return {
		wrapper,
		props
	}
}

describe( 'Purchase Section', () => {
    describe( 'with payment method and no selected rate', () => {
        const { wrapper } = createPurchaseSectionWrapper();
        const renderedPurchaseButton = wrapper.find( PurchaseButton );

		it( 'renders a PurchaseButton', function () {
            expect( renderedPurchaseButton ).to.have.lengthOf( 1 );
		} );
    } );

    describe( 'with payment method and has selected rate', () => {
        const uspsFormRates = {
			available: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
			values: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
        };
        
        const { wrapper } = createPurchaseSectionWrapper( { 
            form: { 
                origin: { values: { country: 'US' } },
                rates: uspsFormRates,
            },
        } );
        const renderedPurchaseButton = wrapper.find( PurchaseButton );

		it( 'renders a PurchaseButton', function () {
            expect( renderedPurchaseButton ).to.have.lengthOf( 1 );
		} );
    } );

	describe( 'with no payment method and no selected rate', () => {
        const { wrapper } = createPurchaseSectionWrapper( { hasLabelsPaymentMethod: false, paymentMethods: [] } );
        const renderedPurchaseButton = wrapper.find( PurchaseButton );

		it( 'renders a PurchaseButton', function () {
            expect( renderedPurchaseButton ).to.have.lengthOf( 1 );
		} );
    } );

    describe( 'with no payment method and has selected non-UPS rate', () => {
        const uspsFormRates = {
			available: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
			values: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
        };
        
        const { wrapper } = createPurchaseSectionWrapper( { 
            form: { 
                origin: { values: { country: 'US' } },
                rates: uspsFormRates,
            },
            hasLabelsPaymentMethod: false, 
            paymentMethods: [], 
        } );
        const renderedCreditCardButton = wrapper.find( CreditCardButton );

		it( 'renders a CreditCardButton', function () {
            expect( renderedCreditCardButton ).to.have.lengthOf( 1 );
		} );
    } );

    describe( 'with no payment method and has selected UPS rate', () => {
        const upsFormRates = {
			available: { default_box: { serviceId: 'Ground', carrierId: 'ups' } },
			values: { default_box: { serviceId: 'Ground', carrierId: 'ups' } },
        };
        
        const { wrapper } = createPurchaseSectionWrapper( { 
            form: { 
                origin: { values: { country: 'US' } },
                rates: upsFormRates,
            },
            hasLabelsPaymentMethod: false, 
            paymentMethods: [], 
        } );
        const renderedPurchaseButton = wrapper.find( PurchaseButton );

		it( 'renders a CreditCardButton', function () {
            expect( renderedPurchaseButton ).to.have.lengthOf( 1 );
		} );
    } );
} );

