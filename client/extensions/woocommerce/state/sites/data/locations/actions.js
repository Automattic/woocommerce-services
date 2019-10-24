/** @format */
/**
 * Internal dependencies
 */
import { setError } from 'woocommerce/state/sites/status/wc-api/actions';
import {
	WOOCOMMERCE_LOCATIONS_REQUEST,
	WOOCOMMERCE_LOCATIONS_REQUEST_SUCCESS,
} from 'woocommerce/state/action-types';
import { loadSettingFromLocalStorage, storeSettingInLocalStorage } from '../../../../woocommerce-services/api/localStorage';


export function fetchLocations( siteId ) {
	const localStorageSettings = loadSettingFromLocalStorage( siteId, 'continents', 60000 );
	if( undefined !== localStorageSettings ) {
		return sucess( siteId, localStorageSettings );
	}

	return {
		type: WOOCOMMERCE_LOCATIONS_REQUEST,
		siteId,
	};
}

export function locationsFailure( siteId, error = false ) {
	const action = fetchLocations( siteId );
	return setError( siteId, action, error );
}

function sucess( siteId, data ) {
	return {
		type: WOOCOMMERCE_LOCATIONS_REQUEST_SUCCESS,
		siteId,
		data,
	};
}

export function locationsReceive( siteId, data ) {
	storeSettingInLocalStorage( siteId, 'continents', data );
	return sucess( siteId, data );
}
