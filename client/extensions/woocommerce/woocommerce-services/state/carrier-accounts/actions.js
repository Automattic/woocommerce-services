/** @format */
/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
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
