/** @format */
/**
 * External dependencies
 */
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice, successNotice } from 'state/notices/actions';
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
} from '../action-types';

export const carrierAccountConnectionSuccess = ( siteId, carrier ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS,
	siteId,
	carrier,
} );

export const updateCarrierSettings = ( siteId, carrier, fieldName, newValue ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
	siteId,
	carrier,
	fieldName,
	newValue,
} );

export const toggleSettingsIsSaving = ( siteId, carrier ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING,
	siteId,
	carrier,
} );

export const toggleShowUPSInvoiceFields = ( siteId, carrier ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	siteId,
	carrier,
} );

export const setVisibilityCancelConnectionDialog = ( siteId, carrier, show ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	siteId,
	carrier,
	show,
} );

export const setVisibilityDisconnectCarrierDialog = ( siteId, carrier, show ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	siteId,
	carrier,
	show,
} );

export const disconnectCarrier = ( siteId, carrier, carrierId ) => ( dispatch ) => {
	dispatch( toggleSettingsIsSaving( siteId, carrier ) );
	return api
		.del( siteId, api.url.shippingCarrierDelete( carrierId ) )
		.then( () => {
			dispatch( successNotice( translate( 'Your carrier account was disconnected succesfully.' ) ) );
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( setVisibilityDisconnectCarrierDialog( siteId, carrier, false ) );
			dispatch( {
				type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
				siteId,
				carrier,
			} );
		} )
		.catch( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( errorNotice( translate( 'There was an error trying to disconnect your carrier account' ) ) );
		} );
};

export const submitCarrierSettings = ( siteId, carrier, values ) => ( dispatch ) => {
	dispatch( toggleSettingsIsSaving( siteId, carrier ) );
	return api
		.post( siteId, api.url.shippingCarrier(), values )
		.then( () => {
			dispatch( carrierAccountConnectionSuccess( siteId, carrier ) );
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( successNotice( translate( 'Your carrier account was connected succesfully.' ) ) );
		} )
		.catch( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( errorNotice( translate( 'There was an error connecting to your UPS account. Please check that all of the information entered matches your UPS account and try to connect again.' ) ) );
		} );
};
