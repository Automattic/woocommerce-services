/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';
import configureMockStore from 'redux-mock-store'
import { Provider } from 'react-redux'
import { act } from 'react-dom/test-utils';

/**
 * Internal dependencies
 */
import SubscriptionsUsage from '..';
import * as api from 'woocommerce/woocommerce-services/api';

const apiPostSpy = jest.spyOn(api, 'post').mockImplementation(() =>
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

const subscriptions = [
	{
		product_key:"W00-bfd64110-4ff9-b179-dca3903f62b0",
		product_keys_all:
			["W00-bfd64110-4ff9-b179-dca3903f62b0"],
		product_id:1770503,
		product_name:"DHL Express rates",
		product_url:"https:\/\/woocommerce.com\/products\/DHL-subscriptions\/",
		key_type:"single",
		key_type_label:"Single site",
		key_parent_order_item_id:null,
		autorenew:true,
		connections:[751464],
		legacy_connections:[],
		shares:[],
		lifetime:false,
		expires:1639008000,
		expired:false,
		expiring:false,
		sites_max:1,
		sites_active:1,
		maxed:false,
		product_status:"publish",
		usage_data: [
			{
				limit: 1000,
				count: 250,
				label: 'Rate Request',
			}
		],
		is_active: true,
	},
	{
		product_key:"W00-bfd64110-4ff9-b179-dca3903f62b1",
		product_keys_all:
			["W00-bfd64110-4ff9-b179-dca3903f62b1"],
		product_id:1770504,
		product_name:"UPS rates",
		product_url:"https:\/\/woocommerce.com\/products\/UPS-subscriptions\/",
		key_type:"single",
		key_type_label:"Single site",
		key_parent_order_item_id:null,
		autorenew:true,
		connections:[751464],
		legacy_connections:[],
		shares:[],
		lifetime:false,
		expires:1639008000,
		expired:false,
		expiring:false,
		sites_max:1,
		sites_active:0,
		maxed:false,
		product_status:"publish",
		usage_data: [
			{
				limit: 500,
				count: 888,
				label: 'Rate Request',
			},
			{
				limit: 50,
				count: 8,
				label: 'Label Purchase',
			}
		],
		is_active:false,
	},

];

describe( 'Subscriptions Usage', () => {
	it('should render nothing when no subscriptions are provided', () => {
		const wrapper = mount(
			<Wrapper>
				<SubscriptionsUsage />
			</Wrapper>
		);

		expect(wrapper.html()).toBeFalsy();
	});

	it('should render a list of subscriptions usage info', () => {
		const wrapper = mount(
			<Wrapper>
				<SubscriptionsUsage subscriptions={ subscriptions } />
			</Wrapper>
		);

		// Find list items for subscriptions.
		const dhlExpressRatesListItem = wrapper.find('.subscriptions-usage__list-item').at(0)
		const upsLabelsListItem = wrapper.find('.subscriptions-usage__list-item').at(1)

		// Expect subscription names.
		expect(dhlExpressRatesListItem.find('span[children="DHL Express rates"]')).toHaveLength(1);
		expect(upsLabelsListItem.find('span[children="UPS rates"]')).toHaveLength(1);

		// Expect subscription usage.
		expect(dhlExpressRatesListItem.find('.subscriptions-usage__list-item-usage-numbers div')).toHaveLength(1);
		expect(upsLabelsListItem.find('.subscriptions-usage__list-item-usage-numbers div')).toHaveLength(2);

		expect(dhlExpressRatesListItem.find('.is-over-limit')).toHaveLength(0);
		expect(upsLabelsListItem.find('.is-over-limit')).toHaveLength(1);
		// Expect button manage.
		const dhlExpressmanageButton = dhlExpressRatesListItem.find('a[href="https://woocommerce.com/my-account/my-subscriptions/"]');
		expect(dhlExpressmanageButton.text()).toBe('Manage');
		// Expect button activate.
		expect(upsLabelsListItem.find('button').text()).toBe('Activate');
	});

	it('should call the API to activate the subscription when clicking on the button', async () => {
		const wrapper = mount(
			<Wrapper>
				<SubscriptionsUsage subscriptions={ subscriptions } />
			</Wrapper>
		);
		const upsLabelsListItem = wrapper.find('.subscriptions-usage__list-item').at(1);
		await act(async () => {
			// Click the Activate button
			upsLabelsListItem.find('button').simulate('click');
		} );
		expect(apiPostSpy).toHaveBeenCalledWith(1234, 'connect/subscription/W00-bfd64110-4ff9-b179-dca3903f62b1/activate');
	});

} );
