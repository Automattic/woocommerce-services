/** @format */

/**
 * External dependencies
 */
import React from "react";
import { expect } from "chai";
import { configure, shallow } from "enzyme";
import Adapter from "enzyme-adapter-react-16";

/**
 * Internal dependencies
 */
import Button from "components/button";
import { CarrierAccountSettings } from "../settings";
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox';

configure({ adapter: new Adapter() });

let props, wrapper;
const toggleShowUPSInvoiceFields = () => {
	props.showUPSInvoiceFields = !props.showUPSInvoiceFields;
	wrapper.setProps( props );
}

function createCarrierAccountSettingsWrapper({ carrier = {} }) {
	props = {
		siteId: 10,
		translate: ( text ) => text,
		carrier: carrier,
		countryNames: {},
		stateNames: {},
		values: {},
		fieldErrors: {},
		showUPSInvoiceFields: false,
		showCancelConnectionDialog: false ,
		toggleShowUPSInvoiceFields: toggleShowUPSInvoiceFields,
	};

	wrapper = shallow(<CarrierAccountSettings {...props} />);

	return { wrapper, props };
}

const visibleFields = [
	[ 'account_number', TextField ],
	[ 'name', TextField ],
	[ 'address', TextField ],
	[ 'address_2', TextField ],
	[ 'city', TextField ],
	[ 'state', Dropdown ],
	[ 'country', Dropdown ],
	[ 'postal_code', TextField ],
	[ 'phone', TextField ],
	[ 'email', TextField ],
	[ 'company_name', TextField ],
	[ 'job_title', TextField ],
	[ 'company_website', TextField ],
	[ 'license_agreement', Checkbox ],
];

const upsInvoiceFields = [
	[ 'ups_invoice_number', TextField ],
	[ 'ups_invoice_date', TextField ],
	[ 'ups_invoice_amount', TextField ],
	[ 'ups_invoice_currency', TextField ],
	[ 'ups_invoice_control_id', TextField ],
]

describe( "Carrier Accounts Settings", () => {
	const { wrapper } = createCarrierAccountSettingsWrapper({
		carrier: "carrier",
	});

	for ( const [ fieldId, fieldType ] of visibleFields) {
		it( `renders a ${fieldId} ${fieldType.name}`, function() {
			const field = wrapper.find( `#${fieldId}` );
			expect( field.is( fieldType ) ).to.equal( true );
		});
	}

	const enableUPSINvoiceFieldsCheckboxWrapper = wrapper.find( '#enable_ups_invoice_fields' );
	enableUPSINvoiceFieldsCheckboxWrapper.simulate( 'change', { target: { checked: true } } );

	for ( const [ fieldId, fieldType ] of upsInvoiceFields) {
		it( `renders the UPS invoice field ${fieldId}`, function() {
			const field = wrapper.find( `#${fieldId}` );
			expect( field.is( fieldType ) ).to.equal( true );
		});
	}

});
