/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';

/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
} from '../../action-types';
import {
	submitCarrierSettings,
	updateCarrierSettings,
	toggleShowUPSInvoiceFields,
	setVisibilityCancelConnectionDialog,
	setVisibilityDisconnectCarrierDialog,
	disconnectCarrier,
} from '../actions';

const siteId = 123;
const carrier = 'carrier';

describe( 'Carrier Accounts state actions', () => {
	test( '#submitCarrierSettings()', () => {
		expect( submitCarrierSettings( siteId, carrier ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
			siteId,
			carrier,
		} );
	} );

	test( '#updateCarriersettings', () => {
		expect( updateCarrierSettings( siteId, carrier, 'fieldId', 'fieldValue' ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
			siteId,
			carrier,
			fieldName: 'fieldId',
			newValue: 'fieldValue',
		} );
	} );

	test( '#toggleshowupsinvoicefields', () => {
		expect( toggleShowUPSInvoiceFields( siteId, carrier ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
			siteId,
			carrier,
		} );
	} );
	test( '#setVisibilityCancelconnectiondialog', () => {
		expect( setVisibilityCancelConnectionDialog( siteId, carrier, true ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
			siteId,
			carrier,
			show: true,
		} );
	} );
	test( '#setVisibilityDisconnectCarrierDialog', () => {
		expect( setVisibilityDisconnectCarrierDialog( siteId, carrier, true ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
			siteId,
			carrier,
			show: true,
		} );
	} );
	test( '#disconnectCarrier', () => {
		expect( disconnectCarrier( siteId, carrier ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
			siteId,
			carrier,
		} );
	} );
} );
