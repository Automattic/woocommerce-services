/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';
import sinon from 'sinon';
import nock from 'nock';

/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
} from '../../action-types';
import {
	carrierAccountConnectionSuccess,
	disconnectCarrier,
	setVisibilityCancelConnectionDialog,
	setVisibilityDisconnectCarrierDialog,
	submitCarrierSettings,
	toggleSettingsIsSaving,
	toggleShowUPSInvoiceFields,
	updateCarrierSettings,
} from '../actions';

const siteId = 123;
const carrier = 'carrier';

const mockRequests = ( valid = true, persist = false ) => {
	const status = valid ? 200 : 500;

	let request = nock( 'https://public-api.wordpress.com:443' );

	if ( persist ) {
		request = request.persist();
	}

	return request.post( `/rest/v1.1/jetpack-blogs/${ siteId }/rest-api/` ).reply( status, {
		data: {
			status,
			body: {
				success: true,
			},
		},
	} );
};

describe( 'Carrier Accounts state actions', () => {
	describe( '#submitCarrierSettings()', () => {
		mockRequests( true, true );

		describe( 'successfully connects the carrier account', () => {
			const dispatchSpy = sinon.spy();
			const settings = {
				field: 'value',
			};

			submitCarrierSettings(
				siteId,
				carrier,
				settings
			)( dispatchSpy ).then( () => {
				it( 'displays a sucess message', () => {
					expect(
						dispatchSpy.calledWith( {
							type: 'NOTICE_CREATE',
							notice: {
								showDismiss: true,
								noticeId: '1',
								status: 'is-success',
								text: 'Your carrier account was connected succesfully.',
							},
						} )
					).to.equal( true );
				} );
				it( 'stops the spinner', () => {
					expect(
						dispatchSpy.calledWith( {
							type: 'WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING',
							siteId,
							carrier,
						} )
					).to.equal( true );
				} );
				it( 'sets a success flag', () => {
					expect(
						dispatchSpy.calledWith( {
							type: 'WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS',
							siteId,
							carrier,
						} )
					).to.equal( true );
				} );
			} );
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

	test( '#carrierAccountConnectionSuccess', () => {
		expect( carrierAccountConnectionSuccess( siteId, carrier ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS,
			siteId,
			carrier,
		} );
	} );

	test( '#toggleSettingsIsSaving', () => {
		expect( toggleSettingsIsSaving( siteId, carrier ) ).to.eql( {
			type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING,
			siteId,
			carrier,
		} );
	} );
} );
