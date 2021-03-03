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
import UpsSettingsForm from '../ups-settings-form';

jest.mock('components/forms/form-text-input', () => {
	return function DummyFormTextField(props) {
		return (
			<div>
				<input className="test__dummy-form-text-field" id={props.id} defaultValue={props.defaultValue} value={props.value} onChange={props.onChange}/>
			</div>
		);
	}
});

const apiPostSpy = jest.spyOn(api, 'post').mockImplementation(() =>
	Promise.resolve({})
);

const mockStore = configureMockStore([]);
const Wrapper = ({children}) => (
	<Provider store={mockStore({
		ui: {
			selectedSiteId: 1234
		},
		plugins: {
			installed: {
				plugins: {
				}
			}
		}
	})}>
		{children}
	</Provider>
);

describe( 'UPS Account Registration Form', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	afterAll( () => {
		jest.restoreAllMocks();
	} );

	it('should prevent the submission of the form if some of the required fields are not entered', async () => {
		const wrapper = mount(
			<Wrapper>
				<UpsSettingsForm />
			</Wrapper>
		);

		await act(async () => {
			wrapper.find('input[name="account_number"]').simulate('change', { target: {
				value: 'A12345'
			} } );
			wrapper.find('input[name="name"]').simulate('change', { target: {
				value: 'FirstName LastName'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'email@something.com'
			} } );
		} );

		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		} );

		expect(apiPostSpy).not.toHaveBeenCalled();
	} );

	it('should prevent the submission of the form if the email is not valid', async () => {
		const wrapper = mount(
			<Wrapper>
				<UpsSettingsForm />
			</Wrapper>
		);

		await act(async () => {
			wrapper.find('input[name="account_number"]').simulate('change', { target: {
				value: 'A12345'
			} } );
			wrapper.find('input[name="name"]').simulate('change', { target: {
				value: 'FirstName LastName'
			} } );
			wrapper.find('input[name="street1"]').simulate('change', { target: {
				value: 'Street 1'
			} } );
			wrapper.find('input[name="city"]').simulate('change', { target: {
				value: 'City'
			} } );
			wrapper.find('input[name="state"]').simulate('change', { target: {
				value: 'MN'
			} } );
			wrapper.find('input[name="postal_code"]').simulate('change', { target: {
				value: '55555'
			} } );
			wrapper.find('input[name="phone"]').simulate('change', { target: {
				value: '2065555555'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'email'
			} } );
			wrapper.find('input[name="title"]').simulate('change', { target: {
				value: 'title'
			} } );
			wrapper.find('input[name="website"]').simulate('change', { target: {
				value: 'http://website.com'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'invalid-email'
			} } );
		} );

		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		} );

		// Enzyme does not play well with hooks, wishing I could do this
		// expect(wrapper.find('span.field-error__input-validation')).toHaveLength(1);
		expect(apiPostSpy).not.toHaveBeenCalled();
	} );

	it('should prevent the submission of the form if the invoice number is not valid', async () => {
		const wrapper = mount(
			<Wrapper>
				<UpsSettingsForm />
			</Wrapper>
		);

		await act(async () => {
			wrapper.find('input[name="account_number"]').simulate('change', { target: {
				value: 'A12345'
			} } );
			wrapper.find('input[name="name"]').simulate('change', { target: {
				value: 'FirstName LastName'
			} } );
			wrapper.find('input[name="street1"]').simulate('change', { target: {
				value: 'Street 1'
			} } );
			wrapper.find('input[name="city"]').simulate('change', { target: {
				value: 'City'
			} } );
			wrapper.find('input[name="state"]').simulate('change', { target: {
				value: 'MN'
			} } );
			wrapper.find('input[name="postal_code"]').simulate('change', { target: {
				value: '55555'
			} } );
			wrapper.find('input[name="phone"]').simulate('change', { target: {
				value: '2065555555'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'email'
			} } );
			wrapper.find('input[name="title"]').simulate('change', { target: {
				value: 'title'
			} } );
			wrapper.find('input[name="website"]').simulate('change', { target: {
				value: 'http://website.com'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'valid-email@email.com'
			} } );
		} );


		await act(async () => {
			wrapper.find('input[id="enable_ups_invoice_fields"]').simulate('change', { target: { 
				checked: true 
			} } );
		} );

		await act(async () => {
			wrapper.update(); // Without this line the new fields are not added after checking the checkbox.
			wrapper.find( 'input[name="invoice_number"]' ).simulate('change', { target: {
				value: 'invalid-invoice-number'
			} } );
		} );

		wrapper.update(); // Update to get the error message.
		expect(wrapper.find('.field-error__input-validation')).toHaveLength(1);
		expect(wrapper.find( '.field-error__input-validation' ).text()).toBe(' The invoice number needs to be 9 or 13 letters and digits in length');

		// Check we can not submit the form.
		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		} );

		expect(apiPostSpy).not.toHaveBeenCalled();
	} );

	it('should submit the form if all the fields are entered', async () => {
		const wrapper = mount(
			<Wrapper>
				<UpsSettingsForm />
			</Wrapper>
		);

		await act(async () => {
			wrapper.find('input[name="account_number"]').simulate('change', { target: {
				value: 'A12345'
			} } );
			wrapper.find('input[name="name"]').simulate('change', { target: {
				value: 'FirstName LastName'
			} } );
			wrapper.find('input[name="street1"]').simulate('change', { target: {
				value: 'Street 1'
			} } );
			wrapper.find('input[name="city"]').simulate('change', { target: {
				value: 'City'
			} } );
			wrapper.find('input[name="state"]').simulate('change', { target: {
				value: 'MN'
			} } );
			wrapper.find('input[name="postal_code"]').simulate('change', { target: {
				value: '55555'
			} } );
			wrapper.find('input[name="phone"]').simulate('change', { target: {
				value: '2065555555'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'email'
			} } );
			wrapper.find('input[name="title"]').simulate('change', { target: {
				value: 'title'
			} } );
			wrapper.find('input[name="website"]').simulate('change', { target: {
				value: 'http://website.com'
			} } );
			wrapper.find('input[name="email"]').simulate('change', { target: {
				value: 'valid-email@email.com'
			} } );
		} );

		await act(async () => {
			// Click the register button
			wrapper.find( 'button.is-primary' ).simulate('click');
		} );

		expect(apiPostSpy).toHaveBeenCalledWith(1234, 'connect/shipping/carrier', {
			account_number: 'A12345',
			city: 'City',
			country: 'US',
			email: 'valid-email@email.com',
			name: 'FirstName LastName',
			phone: '2065555555',
			postal_code: '55555',
			state: 'MN',
			street1: 'Street 1',
			title: 'title',
			type: 'UpsAccount',
			website: 'http://website.com'
		} );
	} );
} );
