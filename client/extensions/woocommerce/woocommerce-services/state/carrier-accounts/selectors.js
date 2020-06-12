/** @format */
/**
 * External dependencies
 */
import { get, isEmpty } from 'lodash';
import { translate } from 'i18n-calypso';
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

export const getFormErrors = ( state, siteId, carrier ) => {
	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const { fieldErrors, ignoreValidation, requiredFields, values } = carrierAccountState.settings;

	if ( isEmpty( values ) ) {
		return {};
	}

	for (const field of requiredFields) {
		const value = values[ field ];
		if ( ! value || ( 'string' === typeof value && '' === value.trim() ) ) {
			fieldErrors[ field ] = translate( 'This field is required' );
		} else {
			delete fieldErrors[ field ];
		}
	}

	if ( ignoreValidation ) {
		Object.keys( fieldErrors ).forEach( field => {
			if ( ignoreValidation[ field ] ) {
				delete fieldErrors[ field ];
			}
		} );
	}
	return fieldErrors;
};

export const getFormValidState = ( state, siteId, carrier ) => {
	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const { fieldErrors, ignoreValidation } = carrierAccountState.settings;
	const noErrors = Object.values( fieldErrors ).every( ( error ) => ! error );
	const allFieldsValidated = Object.values( ignoreValidation ).every( ( ignore ) => ! ignore );

	return noErrors && allFieldsValidated;
};
