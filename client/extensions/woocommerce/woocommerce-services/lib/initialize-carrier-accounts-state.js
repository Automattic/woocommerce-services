/** @format */

/**
 * External dependencies
 */

export default () => {
	const requiredFields = [
		'account_number',
		'name',
		'street1',
		'city',
		'state',
		'country',
		'postal_code',
		'phone',
		'email',
		'name',
		'title',
		'website',
	];

	return {
		UPS: {
			settings: {
				requiredFields,
				values: {
					country: 'US',
					type: 'UpsAccount',
				},
				fieldErrors: {},
				ignoreValidation: requiredFields.reduce( ( accumulator, currentValue ) => {
					accumulator[ currentValue ] = 'country' === currentValue ? false : true;
					return accumulator;
				}, {} ),
				showUPSInvoiceFields: false,
			},
			isSaving: false,
			showDisconnectDialog: false,
		},
	};
};
