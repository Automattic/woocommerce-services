/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
import CompactCard from 'components/card/compact';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import * as api from 'woocommerce/woocommerce-services/api';
import { spy } from 'sinon';

/**
 * Internal dependencies
 */
import { DynamicCarrierAccountSettingsForm, CheckboxFormFieldSet } from '../dynamic-settings-form.js';

configure( { adapter: new Adapter() } );

function createCarrierAccountsWrapper() {
	const props = {
		siteId: 1234,
		translate: ( text ) => text,
        carrierType: 'DhlExpressAccount',
        carrierName: 'DHL Express',
        registrationFields: {
            "account_number": {
                "visibility": "visible",
                "label": "DHL Account Number"
            },
            "country": {
                "visibility": "visible",
                "label": "Account Country Code (2 Letter)"
            },
            "is_reseller": {
                "visibility": "checkbox",
                "label": "Reseller Account? (check if yes)"
            }
        }
	};

	return shallow( <DynamicCarrierAccountSettingsForm { ...props } /> );
}

describe( 'Carrier Account Dynamic Registration Form', () => {
    afterEach(() => {
        jest.clearAllMocks();
      });

	describe( 'with the correct sub-components', () => {
        const wrapper = createCarrierAccountsWrapper();
		it( 'renders 3 fields from the provided props', function () {
			expect(wrapper.find( CompactCard )).to.have.lengthOf(3);
        } );

        it( 'renders 2 visible fields as TextField', function () {
			expect(wrapper.find( TextField )).to.have.lengthOf(2);
        } );

        it( 'renders 1 checkbox fields as Checkbox', function () {
			expect(wrapper.find( CheckboxFormFieldSet )).to.have.lengthOf(1);
		} );
    } );

    describe( 'should submit the form when data are input into the fields', () => {
        const wrapper = createCarrierAccountsWrapper();
        const apiPostSpy = spy(api, 'post');

        /**
         * Note. We uses ShallowMount and we are not able to simulate text typing into <TextField>
         * because it doesn't listen to "change" event, nor any React's SyntheticEvent.
         * We could not use Enzyme.mount either because <TextField> uses Calypso's FormTextInput, which
         * uses the deprecated "refs" in function component. As a reuslt, Enzyme fails to mount and can
         * only do shallow mount here.
         *
         * ref: woocommerce-services/wp-calypso/client/components/forms/form-text-input/index.jsx
         */
        // wrapper.find( '#account_number' ).simulate('change', {target: {
        //     value: 'A123456'
        // }});
        // wrapper.find( '#country' ).simulate('change', {target: {
        //     value: 'US'
        // }});
        wrapper.find( CheckboxFormFieldSet ).simulate('change', {target: {
            checked: true
        }});

        // Click the register button
        wrapper.find( '.carrier-accounts__settings-actions' ).children().first().simulate('click');

        // Expect API to be called
        expect(apiPostSpy.called).to.be.true;
        expect(apiPostSpy).to.have.been.calledWith(1234, 'connect/shipping/carrier', {
            type: 'DhlExpressAccount',
            settings: {
                // account_number: 'A123456',
                // country: 'US',
                is_reseller: true
            }
        });
    } );
} );
