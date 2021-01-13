/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16'
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import { Sidebar } from '../sidebar.js';

configure({ adapter: new Adapter() });

function createSidebarWrapper( initProps = {} ) {
	const props = {
		orderId: 1000,
		siteId: 10,
		translate: (text) => text,
		order: {
			status: 'processing',
		},
		paperSize: 'letter',
		errors: {},
		form: {
			origin: { values: { country: 'US' } },
			rates: {},
		},
		updatePaperSize: () => true,
		fulfillOrder: true,
		emailDetails: true,
		hasLabelsPaymentMethod: true,
		setFulfillOrderOption: jest.fn(),
		setEmailDetailsOption: jest.fn(),
		...initProps,
	};

	const wrapper = shallow( <Sidebar { ...props } /> );

	return {
		wrapper,
		props
	}
}

describe( 'Sidebar', () => {
	describe( 'for default order state', () => {
		const { wrapper, props } = createSidebarWrapper();
		const renderedCheckboxControl = wrapper.find( CheckboxControl );

		it( 'renders a checkbox control', function () {
			expect( renderedCheckboxControl ).to.have.lengthOf( 1 );
		} );

		it( 'Has the Correct Label', function () {
			expect( renderedCheckboxControl.props().label ).to.equal( 'Mark this order as complete and notify the customer' );
		} );

		it( 'Unchecked checkbox disables fulfilling the order and sending email', function() {
			renderedCheckboxControl.props().onChange( false )
			expect( props.setFulfillOrderOption.mock.calls ).to.have.lengthOf( 1 );
			expect( props.setFulfillOrderOption.mock.calls[0][2] ).to.equal( false );
			expect( props.setEmailDetailsOption.mock.calls ).to.have.lengthOf( 0 );
		} );

		it( 'it is checked', function() {
			expect( renderedCheckboxControl.props().checked ).to.equal( true );
		} );

		it( 'Has paper size dropdown', function () {
			const paperDropdown = wrapper.find( Dropdown );
			const paperDropdownProps = paperDropdown.props();
			expect( paperDropdown ).to.have.lengthOf( 1 );
			expect( paperDropdownProps.title ).to.equal( 'Paper size' );
			expect( paperDropdownProps.value ).to.equal( props.paperSize );
		} );

	} );
	describe( 'for completed orders', () => {
		const { wrapper, props } = createSidebarWrapper( { order: { status: 'completed' }, fulfillOrder: false, emailDetails: false } );
		const renderedCheckboxControl = wrapper.find( CheckboxControl );

		it( 'Has a the Correct Label', function () {
			expect( renderedCheckboxControl.props().label ).to.equal( 'Notify the customer with shipment details' );
		} );

		it( 'it is not checked', function() {
			expect( renderedCheckboxControl.props().checked ).to.equal( false );
		} );

		it( 'Unchecked checkbox disables sending email', function() {
			renderedCheckboxControl.props().onChange( false );
			expect( props.setFulfillOrderOption.mock.calls ).to.have.lengthOf( 0 );
			expect( props.setEmailDetailsOption.mock.calls ).to.have.lengthOf( 1 );
			expect( props.setEmailDetailsOption.mock.calls[0][2] ).to.equal( false );
		} );
	} );
	describe( 'for no payment method and no selected rates', () => {
		const { wrapper } = createSidebarWrapper( { hasLabelsPaymentMethod: false } );

		it( 'has paper size dropdown', function () {
			expect( wrapper.find( Dropdown ) ).to.have.lengthOf( 1 );
		} );
	} );

	describe( 'for no payment method and UPS selected rate', () => {
		const upsFormRates = {
			available: { default_box: { serviceId: 'Ground', carrierId: 'ups' } },
			values: { default_box: { serviceId: 'Ground', carrierId: 'ups' } },
		};

		const { wrapper } = createSidebarWrapper( {
			form: {
				origin: { values: { country: 'US' } },
				rates: upsFormRates,
			},
			hasLabelsPaymentMethod: false
		} );

		it( 'has paper size dropdown', function () {
			expect( wrapper.find( Dropdown ) ).to.have.lengthOf( 1 );
		} );
	} );

	describe( 'for no payment method and non-UPS selected rate', () => {
		const uspsFormRates = {
			available: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
			values: { default_box: { serviceId: 'Priority', carrierId: 'usps' } },
		};

		const { wrapper } = createSidebarWrapper( {
			form: {
				origin: { values: { country: 'US' } },
				rates: uspsFormRates,
			},
			hasLabelsPaymentMethod: false
		} );

		it( 'does not have paper size dropdown', function () {
			expect( wrapper.find( Dropdown ) ).to.have.lengthOf( 0 );
		} );
	} );
} );
