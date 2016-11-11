import { PhoneNumberUtil, AsYouTypeFormatter } from 'google-libphonenumber';

export const isValidPhone = ( phoneNumber, country ) => {
	const phoneUtil = PhoneNumberUtil.getInstance();
	try {
		const parsedPhone = phoneUtil.parse( phoneNumber, country );
		return phoneUtil.isValidNumber( parsedPhone );
	} catch ( e ) {
		return false;
	}
};

export const getPlainPhoneNumber = ( phoneNumber, countryCode ) => {
	if ( ! isValidPhone( phoneNumber, countryCode ) ) {
		return phoneNumber;
	}
	return '' + PhoneNumberUtil.getInstance().parse( phoneNumber, countryCode ).getNationalNumber();
};

export const formatPhoneForDisplay = ( rawNumber, countryCode ) => {
	const formatter = new AsYouTypeFormatter( countryCode );
	let phoneNumber = '';
	rawNumber.split( '' ).forEach( ( char ) => {
		phoneNumber = formatter.inputDigit( char );
	} );
	return phoneNumber;
};
