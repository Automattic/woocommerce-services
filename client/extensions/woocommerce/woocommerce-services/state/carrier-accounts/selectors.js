/** @format */
/**
 * External dependencies
 */
import { get } from 'lodash';
/**
 * Internal dependencies
 */
import { getSelectedSiteId } from 'state/ui/selectors';

export const getCarrierAccountsState = ( state, siteId = getSelectedSiteId( state ), carrier ) => {
	return get(
		state,
		[ 'extensions', 'woocommerce', 'woocommerceServices', siteId, 'carrierAccounts', carrier ],
		null
	);
};
