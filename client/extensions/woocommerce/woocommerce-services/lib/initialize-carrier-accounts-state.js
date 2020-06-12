/** @format */

/**
 * External dependencies
 */

export default () => {
	const requiredFields = [
		'account_number',
		'name',
		'address',
		'city',
		'state',
		'state',
		'country',
		'postal_code',
		'phone',
		'email',
		'company_name',
		'job_title',
		'company_website',
		'license_agreement',
	];

	return {
		UPS: {
			settings: {
				requiredFields,
				values:{
					country: 'US'
				},
				fieldErrors: {},
				ignoreValidation: requiredFields.reduce( (accumulator, currentValue) => {   
					accumulator[ currentValue ] = 'country' === currentValue ? false : true;
					return accumulator
				}, {} ),
				showUPSInvoiceFields: false,
			}
		}
	};
}
