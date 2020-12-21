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
import SubscriptionsUsage from '..';

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
	it('should render nothing when no subscriptions are provided', () => {
		const wrapper = mount(
			<Wrapper>
				<SubscriptionsUsage />
			</Wrapper>
		);

		expect(wrapper.html()).toBeFalsy();
	});

	it('should render a list of subscriptions usage info', () => {

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
				usage_limit:1000,
				usage_count:250
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
				sites_active:1,
				maxed:false,
				product_status:"publish",
				usage_limit:50,
				usage_count:8
			},

		];
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
		expect(dhlExpressRatesListItem.find('span[children="250/1000"]')).toHaveLength(1);
		expect(upsLabelsListItem.find('span[children="8/50"]')).toHaveLength(1);
		
		// Expect button manage.
		const dhlExpressmanageButton = dhlExpressRatesListItem.find('a[href="https://woocommerce.com/my-account/my-subscriptions/"]');
		expect(dhlExpressmanageButton.text()).toBe('Manage');
		const upsLabelsmanageButton = upsLabelsListItem.find('a[href="https://woocommerce.com/my-account/my-subscriptions/"]');
		expect(upsLabelsmanageButton.text()).toBe('Manage');
	
	});

} );
