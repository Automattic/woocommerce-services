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
		'ups_invoice_number',
		'ups_invoice_date',
		'ups_invoice_amount',
		'ups_invoice_currency',
		'ups_invoice_control_id',
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
			}
		}
	};
}
