export const isValidPhone = ( phoneNumber ) => {
	return 0 !== ( '' + phoneNumber ).length;
};

export const getPlainPhoneNumber = ( phoneNumber, countryCode ) => {
	if ( ! isValidPhone( phoneNumber, countryCode ) ) {
		return phoneNumber;
	}
	return phoneNumber;
};

export const formatPhoneForDisplay = ( phoneNumber ) => {
	return '' + phoneNumber;
};
