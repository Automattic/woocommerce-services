/** @format */

let jQuery;

// Mirrors core's filter_data() in meta-boxes-order.js.
const filterRecalculateData = ( data ) => {
	const filtered = jQuery( '#woocommerce-order-items' ).triggerHandler(
		'woocommerce_order_meta_box_recalculate_ajax_data',
		[ data ]
	);

	return filtered ? filtered : data;
};

// Loads the script. The test must use the same jQuery instance as the script,
// because jQuery keeps handlers per instance and resetModules() makes a new one.
const loadScript = ( taxBasedOn ) => {
	global.woocommerce_admin_meta_boxes = { tax_based_on: taxBasedOn };
	jQuery = require( 'jquery' );
	require( '../new-order-taxjar' );
};

const renderOrderScreen = ( { shippingCountry, shippingStreet, billingCountry, billingStreet } ) => {
	document.body.innerHTML = `
		<div id="woocommerce-order-items"></div>
		<input id="_shipping_country" value="${ shippingCountry }" />
		<input id="_shipping_address_1" value="${ shippingStreet }" />
		<input id="_billing_country" value="${ billingCountry }" />
		<input id="_billing_address_1" value="${ billingStreet }" />
	`;
};

describe( 'new-order-taxjar recalculate street', () => {
	beforeEach( () => {
		jest.resetModules();
		renderOrderScreen( {
			shippingCountry: 'US',
			shippingStreet: '24500 Highway 145',
			billingCountry: 'US',
			billingStreet: '400 Central Ave',
		} );
	} );

	afterEach( () => {
		delete global.woocommerce_admin_meta_boxes;
		document.body.innerHTML = '';
	} );

	const recalculateWith = ( taxBasedOn ) => {
		loadScript( taxBasedOn );

		return filterRecalculateData( {
			action: 'woocommerce_calc_line_taxes',
			country: 'US',
			postcode: '81323',
		} );
	};

	it( 'sends the shipping street when taxes are based on shipping', () => {
		expect( recalculateWith( 'shipping' ).street ).toBe( '24500 Highway 145' );
	} );

	it( 'sends the billing street when taxes are based on billing', () => {
		expect( recalculateWith( 'billing' ).street ).toBe( '400 Central Ave' );
	} );

	it( 'falls back to the billing street when the order has no shipping country', () => {
		document.getElementById( '_shipping_country' ).value = '';

		expect( recalculateWith( 'shipping' ).street ).toBe( '400 Central Ave' );
	} );

	it( 'sends the billing street when taxes are based on the store address, as core sends billing fields', () => {
		expect( recalculateWith( 'base' ).street ).toBe( '400 Central Ave' );
	} );

	it( 'reads the street from the form as edited, not as loaded', () => {
		loadScript( 'shipping' );
		document.getElementById( '_shipping_address_1' ).value = '1 Edited St';

		expect( filterRecalculateData( {} ).street ).toBe( '1 Edited St' );
	} );

	it( 'keeps the data core built', () => {
		expect( recalculateWith( 'shipping' ) ).toEqual( {
			action: 'woocommerce_calc_line_taxes',
			country: 'US',
			postcode: '81323',
			street: '24500 Highway 145',
		} );
	} );

	it( 'keeps data with quotes intact', () => {
		document.getElementById( '_shipping_address_1' ).value = 'Unit "B", 24500 Highway 145';

		expect( recalculateWith( 'shipping' ).street ).toBe( 'Unit "B", 24500 Highway 145' );
	} );
} );
