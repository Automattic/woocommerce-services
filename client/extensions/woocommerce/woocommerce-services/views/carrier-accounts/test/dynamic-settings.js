/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
/**
 * Internal dependencies
 */
import { DynamicCarrierAccountSettings } from '../dynamic-settings.js';

configure( { adapter: new Adapter() } );

const mockCarrierRegistrationFieldsState = [{
    "type": "DhlExpressAccount",
    "name": "DHL Express",
    "fields": {
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
}];

/**
 * useEffect not called when shallow mount. Refer to bug https://github.com/enzymejs/enzyme/issues/2086
 * Here, we try to start the component with a pre-defined state.
 */
jest.mock('react', () => ({
    ...jest.requireActual('react'),
    useState: () => ([mockCarrierRegistrationFieldsState]),
}));

describe( 'Dynamic carrier registration settings', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

	describe( 'with supported carrier', () => {
        const props = {
            siteId: 1234,
            carrier: 'DhlExpressAccount'
        };
		it( 'should render the Connect, Localized, and DynamicCarrierAccountSettingsForm sub components', function () {
            const wrapper = shallow( <DynamicCarrierAccountSettings { ...props } /> );
			expect(wrapper.text()).to.match(/Connect\(Localized\(DynamicCarrierAccountSettingsForm/);
        } );
    } );

    describe( 'with non-supported carrier', () => {
        const props = {
            siteId: 1234,
            carrier: 'ASDASDASD'
        };
		it( 'should render the Connect, Localized, and DynamicCarrierAccountSettingsForm sub components', function () {
            const wrapper = shallow( <DynamicCarrierAccountSettings { ...props } /> );
            expect(wrapper.text()).to.equal('ASDASDASD not supported.');
        } );
    } );
} );