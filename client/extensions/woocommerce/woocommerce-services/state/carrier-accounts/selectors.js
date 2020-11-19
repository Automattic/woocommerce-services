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

const emailRegex = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
const USPostalCodeRegex = /^\d{5}$/;
const UPSAccountNumberRegex = /^[a-zA-Z0-9]{6}$/;
const UPSInvoiceNumberRegex = /^(\d{9}|\d{13})$/;
const phoneRegex = /^\d{10}$/;
const dateRegex = /^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/;
const urlRegex = /^(?:(?:(?:https?|ftp):)?\/\/)*(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z0-9\u00a1-\uffff][a-z0-9\u00a1-\uffff_-]{0,62})?[a-z0-9\u00a1-\uffff]\.)+(?:[a-z\u00a1-\uffff]{2,}\.?))(?::\d{2,5})?(?:[/?#]\S*)?$/i;

export const getFormErrors = ( state, siteId, carrier ) => {
	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const { fieldErrors, ignoreValidation, requiredFields, values } = carrierAccountState.settings;

	if ( isEmpty( values ) ) {
		return {};
	}

	for ( const field of requiredFields ) {
		const value = values[ field ];
		if ( ! value || ( 'string' === typeof value && '' === value.trim() ) ) {
			fieldErrors[ field ] = translate( 'This field is required' );
		} else {
			delete fieldErrors[ field ];
		}
	}

	if ( values.account_number && ! values.account_number.match( UPSAccountNumberRegex ) ) {
		fieldErrors.account_number = translate( 'The UPS account number needs to be 6 letters and digits in length' );
	}

	if ( values.phone && ! values.phone.match( phoneRegex ) ) {
		fieldErrors.phone = translate( 'The phone number needs to be 10 digits in length' );
	}

	if ( values.country === 'US' && values.postal_code && ! values.postal_code.match( USPostalCodeRegex ) ) {
		fieldErrors.postal_code = translate( 'The ZIP/Postal code needs to be 5 digits in length' );
	}

	if ( values.email && ! values.email.match( emailRegex ) ) {
		fieldErrors.email = translate( 'The email format is not valid' );
	}

	if ( values.website && ! values.website.match( urlRegex ) ) {
		fieldErrors.website = translate( 'The company website format is not valid' );
	}

	if ( values.invoice_number && ! values.invoice_number.match( UPSInvoiceNumberRegex ) ) {
		fieldErrors.invoice_number = translate( 'The invoice number needs to be 9 or 13 digits in length' );
	} else {
		delete fieldErrors.invoice_number;
	}

	if ( values.invoice_date && ! values.invoice_date.match( dateRegex ) ) {
		fieldErrors.invoice_date = translate( 'The date must be a valid date in the format YYYY-MM-DD' );
	} else {
		delete fieldErrors.invoice_date;
	}

	if ( ignoreValidation ) {
		Object.keys( fieldErrors ).forEach( ( field ) => {
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
