/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';
import * as api from 'woocommerce/woocommerce-services/api';
import { act } from 'react-dom/test-utils';
import { Provider } from 'react-redux';
import configureMockStore from 'redux-mock-store';

/**
 * Internal dependencies
 */
import DynamicCarrierAccountSettingsForm from '../dynamic-settings-form';

jest.mock('components/forms/form-text-input', () => {
	return function DummyFormTextField(props) {
		return (
		<div>
			<input className="test__dummy-form-text-field" id={props.id} defaultValue={props.defaultValue} value={props.value} onChange={props.onChange}/>
		</div>
		);
	}
});

const apiSpy = jest.spyOn(api, 'post').mockImplementation(() =>
	Promise.resolve({})
);

const mockStore = configureMockStore([]);
const Wrapper = ({children}) => (
	<Provider store={mockStore({
		ui: {
			selectedSiteId: 1234
		}
	})}>
		{children}
	</Provider>
);

describe( 'Carrier Account Dynamic Registration Form', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	afterAll( () => {
		jest.restoreAllMocks();
	} );

	it('does not break if no fields are passed', () => {
		const wrapper = mount(
			<Wrapper><DynamicCarrierAccountSettingsForm carrierType="CarrierAccount" carrierName="DHL Express" /></Wrapper>
		);

		expect(wrapper.find('h4').at(0).text()).toBe('Connect your DHL Express account');
		// Expect the second children on the form, the form fields, to be empty. First child is title, third is the buttons.
		expect(wrapper.find('.carrier-accounts__settings-form').children().at(1).text()).toBeFalsy();
	});

	it('renders the carrier text fields', async () => {
		const wrapper = mount(
			<Wrapper>
				<DynamicCarrierAccountSettingsForm
					carrierType="CarrierAccount"
					carrierName="DHL Express"
					registrationFields={{
						account_number: {
							visibility: 'visible',
							label: 'Account Number'
						},
						country_code: {
							visibility: 'visible',
							label: 'Country Code'
						},
					}}
				/>
			</Wrapper>
		);

		const accountNumberField = wrapper.find('input[name="account_number"]');
		const countryField = wrapper.find('input[name="country_code"]');

		expect(accountNumberField).toHaveLength(1);
		expect(countryField).toHaveLength(1);

		await act(async () => {
			accountNumberField.simulate('change', {target: {
				value: 'A123456'
			}});
			countryField.simulate('change', {target: {
				value: 'US'
			}});
		});

		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		});

		expect(apiSpy).toHaveBeenCalledTimes(1);
		expect(apiSpy).toHaveBeenCalledWith(1234, 'connect/shipping/carrier', {
				type: 'CarrierAccount',
				account_number: 'A123456',
				country_code: 'US',
			}
		);
	});

	it('renders the carrier checkbox and text fields', async () => {
		const wrapper = mount(
			<Wrapper>
				<DynamicCarrierAccountSettingsForm
					carrierType="CarrierAccount"
					carrierName="DHL Express"
					registrationFields={{
						is_reseller: {
							visibility: 'checkbox',
							label: 'Is reseller?'
						},
						country_code: {
							visibility: 'visible',
							label: 'Country Code'
						},
					}}
				/>
			</Wrapper>
		);

		const isResellerField = wrapper.find('input[id="is_reseller"]')
		const countryField = wrapper.find('input[name="country_code"]')

		expect(isResellerField).toHaveLength(1);
		expect(countryField).toHaveLength(1);

		await act(async () => {
			isResellerField.simulate('change', {target: {
				checked: true
			}});
			countryField.simulate('change', {target: {
				value: 'US'
			}});
		});

		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		});

		expect(apiSpy).toHaveBeenCalledTimes(1);
		expect(apiSpy).toHaveBeenCalledWith(1234, 'connect/shipping/carrier', {
				type: 'CarrierAccount',
				is_reseller: true,
				country_code: 'US',
			}
		);
	});

	it('renders the default carrier description', () => {
		const wrapper = mount(
			<Wrapper>
				<DynamicCarrierAccountSettingsForm
					carrierType="CarrierAccount"
					carrierName="DHL Express"
					registrationFields={{
						is_reseller: {
							visibility: 'checkbox',
							label: 'Is reseller?'
						},
						country_code: {
							visibility: 'visible',
							label: 'Country Code'
						},
					}}
				/>
			</Wrapper>
		);

		const description = wrapper.find('.carrier-accounts__settings-subheader-description');
		expect(description.text()).toBe('Set up your own carrier account to compare rates and print labels from multiple carriers in WooCommerce Shipping. Learn more about adding carrier accounts.');
	});

	it('renders the provided carrier description', () => {
		const wrapper = mount(
			<Wrapper>
				<DynamicCarrierAccountSettingsForm
					carrierType="CarrierAccount"
					carrierName="DHL Express"
					carrierDescription="Carrier description sent by server."
					registrationFields={{
						is_reseller: {
							visibility: 'checkbox',
							label: 'Is reseller?'
						},
						country_code: {
							visibility: 'visible',
							label: 'Country Code'
						},
					}}
				/>
			</Wrapper>
		);

		const description = wrapper.find('.carrier-accounts__settings-subheader-description');
		expect(description.text()).toBe('Carrier description sent by server.');
	});
} );
