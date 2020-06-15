/** @format */
/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
} from '../action-types';


export const submitCarrierSettings = ( siteId, carrier ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
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
