/** @format */
/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SHOW_SETTINGS,
} from '../action-types';

export const showSettings = ( siteId, carrier ) => ( {
	type: WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SHOW_SETTINGS,
	siteId,
	carrier,
} );
