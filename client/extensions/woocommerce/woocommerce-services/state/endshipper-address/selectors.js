/** @format */

/**
 * External dependencies
 */

import { get } from 'lodash';
/**
 * Internal dependencies
 */
import { getSelectedSiteId } from 'state/ui/selectors';

export const getEndShipperAddressForm = ( state, siteId = getSelectedSiteId( state ) ) => {
	console.log( 'test state' );
	console.log( state );
	return get(
		state,
		[ 'extensions', 'woocommerce', 'woocommerceServices', siteId, 'endShipperAddress' ],
		null
	);
};

export const getEndShipperAddressFormData = ( state, siteId = getSelectedSiteId( state ) ) => {
	const form = getEndShipperAddressForm( state, siteId );
	return form && form.data;
};

export const getEndShipperAddressFormMeta = ( state, siteId = getSelectedSiteId( state ) ) => {
	const form = getEndShipperAddressForm( state, siteId );
	return form && form.meta;
};

export const getEndShipperAddressStoreOptions = ( state, siteId = getSelectedSiteId( state ) ) => {
	const form = getEndShipperAddressForm( state, siteId );
	return form && form.storeOptions;
};

export const getEndShipperAddressUserMeta = ( state, siteId = getSelectedSiteId( state ) ) => {
	const formMeta = getEndShipperAddressFormMeta( state, siteId );
	return formMeta && formMeta.user;
};

export const areSettingsLoaded = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return meta && meta.isLoaded;
};

export const areSettingsFetching = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return meta && meta.isFetching;
};

export const areSettingsErrored = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return meta && meta.isFetchError;
};

export const areEndShipperEnabled = ( state, siteId = getSelectedSiteId( state ) ) => {
	const data = getEndShipperAddressFormData( state, siteId );
	return data && data.enabled;
};

export const isPristine = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return meta && meta.pristine;
};

export const userCanEditSettings = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return meta && meta.can_edit_settings;
};

export const getMasterUserInfo = ( state, siteId = getSelectedSiteId( state ) ) => {
	const meta = getEndShipperAddressFormMeta( state, siteId );
	return {
		masterUserName: meta && meta.master_user_name,
		masterUserLogin: meta && meta.master_user_login,
		masterUserEmail: meta && meta.master_user_email,
		masterUserWpcomLogin: meta && meta.master_user_wpcom_login,
	};
};
