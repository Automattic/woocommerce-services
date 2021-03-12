/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';

import LiveRatesCarriersList from '..'

/* eslint-disable wpcalypso/jsx-classname-namespace */
describe('LiveRatesCarriersList', () => {
	it('should not render when the list of accounts is empty', () => {
		const wrapper = mount(<LiveRatesCarriersList carrierIds={[]} />);

		expect(wrapper.html()).toBeFalsy();
	});

	it('should not render when the list of accounts contains invalid carrier IDs', () => {
		const wrapper = mount(<LiveRatesCarriersList carrierIds={['wc_services_fedex', 'wc_services_dhlecommerceasia']} />);

		expect(wrapper.html()).toBeFalsy();
	});

	it('should render the USPS account information', () => {
		const wrapper = mount(<LiveRatesCarriersList carrierIds={['wc_services_usps']} />);

		expect(wrapper.contains(<div className="live-rates-carriers-list__carrier">USPS</div>)).toBe(true);
		expect(wrapper.contains(<div className="live-rates-carriers-list__carrier">DHL Express</div>)).toBe(false);
	});

	it('should render the DHL Express account information', () => {
		const wrapper = mount(<LiveRatesCarriersList carrierIds={['wc_services_dhlexpress']} />);

		expect(wrapper.contains(<div className="live-rates-carriers-list__carrier">USPS</div>)).toBe(false);
		expect(wrapper.contains(<div className="live-rates-carriers-list__carrier">DHL Express</div>)).toBe(true);
	});
});
