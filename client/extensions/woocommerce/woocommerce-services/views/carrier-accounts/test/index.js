/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';
import configureMockStore from 'redux-mock-store'
import { Provider } from 'react-redux'

/**
 * Internal dependencies
 */
import CarrierAccounts from '..';

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

describe( 'Carrier Accounts', () => {
	it('should render nothing when no accounts are provided', () => {
		const wrapper = mount(
			<Wrapper>
				<CarrierAccounts />
			</Wrapper>
		);

		expect(wrapper.html()).toBeFalsy();
	});

	it('should render a list of provided accounts', () => {
		const wrapper = mount(
			<Wrapper>
				<CarrierAccounts accounts={[
					{
						carrier: 'DHL Express',
						type: 'DhlExpressAccount',
						id: null,
						account: null,
					},
					{
						carrier: 'UPS',
						type: 'UpsAccount',
						id: null,
						account: null,
					},
				]} />
			</Wrapper>
		);

		// when no accounts are connected, do not show the "credentials" heading
		expect(wrapper.find('.carrier-accounts__header-credentials')).toHaveLength(0);

		const dhlExpressRow = wrapper.find('.carrier-accounts__list-item').at(0)
		const upsRow = wrapper.find('.carrier-accounts__list-item').at(1)

		expect(dhlExpressRow.find('span[children="DHL Express"]')).toHaveLength(1);
		expect(upsRow.find('span[children="UPS"]')).toHaveLength(1);

		const dhlConnectButton = dhlExpressRow.find('a[href*="carrier=DhlExpressAccount"]');
		expect(dhlConnectButton.text()).toBe('Connect');
	});

	it('should render connected accounts', () => {
		const wrapper = mount(
			<Wrapper>
				<CarrierAccounts accounts={[
					{
						carrier: 'UPS',
						type: 'UpsAccount',
						id: null,
						account: null,
					},
					{
						carrier: 'DHL Express',
						type: 'DhlExpressAccount',
						id: 'a_1234',
						account: 'a12xxx',
					},
				]} />
			</Wrapper>
		);

		// when at least one account is connected, show the "credentials" heading
		expect(wrapper.find('.carrier-accounts__header-credentials')).toHaveLength(1);

		const dhlExpressRow = wrapper.find('.carrier-accounts__list-item').at(1);

		// displays the account number
		expect(dhlExpressRow.find('span[children="a12xxx"]')).toHaveLength(1);

		// displays the disconnect button
		expect(dhlExpressRow.find('button').text()).toBe('Disconnect');
	});

	it('should display a dialog to disconnect an account', async() => {
		const wrapper = mount(
			<Wrapper>
				<CarrierAccounts accounts={[
					{
						carrier: 'UPS',
						type: 'UpsAccount',
						id: 'a_1234',
						account: 'a12xxx',
					},
				]} />
			</Wrapper>
		);

		const upsRow = wrapper.find('.carrier-accounts__list-item').at(0);

		// displays the disconnect button
		const disconnectButton = upsRow.find('button')
		expect(disconnectButton.text()).toBe('Disconnect');

		// when migrating to React Testing Library,
		// you'll be able to just `expect` that the text of the dialog is displayed or not (rather than inspecting the props)
		// and also to trigger the "change" (unfortunately Jest doesn't have good support to hooks)
		expect(wrapper.find('ButtonModal[isVisible=false]')).toHaveLength(1);
	});
} );
