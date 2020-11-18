/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, mount } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
import * as api from 'woocommerce/woocommerce-services/api';
import { act } from 'react-dom/test-utils';
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { DynamicCarrierAccountSettings } from '../dynamic-settings.js';

configure( { adapter: new Adapter() } );

const mockCarrierRegistrationFieldsState = [{
	"type": "DhlExpressAccount",
	"name": "Test DHL Express",
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

jest.mock('../dynamic-settings-form', () => {
	return function DummyDynamicSettingsForm(props) {
		return (
		<div>
			{JSON.stringify(props)}
		</div>
		);
	}
});

describe( 'Dynamic carrier registration settings', () => {
	beforeEach(() => {
		jest.spyOn(api, "get").mockImplementation(() =>
			Promise.resolve({
				carriers: mockCarrierRegistrationFieldsState
			})
		);
	});

	afterEach(() => {
		jest.clearAllMocks();
	});

	describe( 'with supported carrier', () => {
		const props = {
			siteId: 1234,
			carrier: 'DhlExpressAccount',
			translate: translate
		};


		it( 'should render the Connect, Localized, and DynamicCarrierAccountSettingsForm sub components', async () => {
			let wrapper;
			await act(async () => {
				wrapper = mount(<DynamicCarrierAccountSettings { ...props } />);
			});

			const actualDynamicCarrierAccountSettingsFormProps = JSON.parse(wrapper.text());
			expect(actualDynamicCarrierAccountSettingsFormProps.carrierName).to.equal(mockCarrierRegistrationFieldsState[0].name);
			expect(actualDynamicCarrierAccountSettingsFormProps.carrierType).to.equal(mockCarrierRegistrationFieldsState[0].type);
			expect(actualDynamicCarrierAccountSettingsFormProps.registrationFields).to.deep.equal(mockCarrierRegistrationFieldsState[0].fields);
		} );
	} );

	describe( 'with non-supported carrier', () => {
		const props = {
			siteId: 1234,
			carrier: 'ASDASDASD',
			translate: translate
		};


		it( 'should display not supported message', async () => {
			let wrapper;
			await act(async () => {
				wrapper = mount(<DynamicCarrierAccountSettings { ...props } />);
			});
			expect(wrapper.text()).to.equal('ASDASDASD not supported.');
		} );
	} );
} );

describe( 'Dynamic carrier registration settings with pending promises', () => {
	/**
	 * Moved this into its own describe block to test pending promise
	 * so that it doesn't change all the async-await promises in the above
	 * test cases.
	 */
	beforeEach(() => {
		jest.spyOn(api, "get").mockImplementation(() =>
			Promise.resolve({})
		);
	});

	afterEach(() => {
		jest.clearAllMocks();
	});

	const props = {
		siteId: 1234,
		carrier: 'DhlExpressAccount',
		translate: translate
	};

	it( 'should display a loading message', async () => {
		let wrapper;
		await act(async () => {
			wrapper = mount(<DynamicCarrierAccountSettings { ...props } />);
		});
		expect(wrapper.text()).to.equal('Loading...');
	} );
} );