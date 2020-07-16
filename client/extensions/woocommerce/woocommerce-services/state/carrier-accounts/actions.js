/** @format */
/**
 * External dependencies
 */
import { translate } from 'i18n-calypso';
import { omit } from 'lodash';

/**
 * Internal dependencies
 */
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice, successNotice } from 'state/notices/actions';
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
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

export const disconnectCarrier = ( siteId, carrier ) => ( dispatch ) => {
	return api
		.del( siteId, api.url.shippingCarrierDelete( carrier ) )
		.then( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( successNotice( translate( 'Your carrier account was disconnected succesfully.' ) ) );
		} )
		.catch( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( errorNotice( translate( 'There was an error trying to disconnect your carrier account' ) ) );
		} );
};

export const submitCarrierSettings = ( siteId, carrier, values ) => ( dispatch ) => {
	return api
		.post( siteId, api.url.shippingCarrier(), omit( values, [ 'license_agreement' ] ) )
		.then( () => {
			dispatch( carrierAccountConnectionSuccess( siteId, carrier ) );
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( successNotice( translate( 'Your carrier account was connected succesfully.' ) ) );
		} )
		.catch( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( errorNotice( translate( 'There was an error trying to connect your carrier account' ) ) );
		} );
};

export const getCarriers = ( siteId, carrier, values ) => ( dispatch ) => {
	return api
		.post( siteId, api.url.shippingCarrier(), omit( values, [ 'license_agreement' ] ) )
		.then( () => {
			dispatch( carrierAccountConnectionSuccess( siteId, carrier ) );
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( successNotice( translate( 'Your carrier account was connected succesfully.' ) ) );
		} )
		.catch( () => {
			dispatch( toggleSettingsIsSaving( siteId, carrier ) );
			dispatch( errorNotice( translate( 'There was an error trying to connect your carrier account' ) ) );
		} );
};

export const fetchCarriers = ( siteId ) => ( dispatch ) => {
	api.get( siteId, api.url.shippingCarriers )
		.then( ( carriers ) => {
			console.log( carriers );
			dispatch( initForm( siteId, storeOptions, formData, formMeta, userMeta ) );
		} )
		.catch( ( error ) => {
			//dispatch( setFormMetaProperty( siteId, 'isFetchError', true ) );
			console.error( error ); // eslint-disable-line no-console
		} );
	//.then( () => dispatch( setFormMetaProperty( siteId, 'isFetching', false ) ) );
};
