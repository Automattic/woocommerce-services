import validator from 'is-my-json-valid';

export const isFieldError = ( required, schema, value ) => {
	if ( required && ! value ) {
		return true;
	}

	if ( ! required && ! value ) {
		return false;
	}

	const validate = validator( schema );

	return ( false === validate( value ) );
};
