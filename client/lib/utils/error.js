export const isFieldError = ( required, schema, value ) => {
	if ( required && ! value ) {
		return true;
	}

	if ( schema.pattern ) {
		const pattern = new RegExp( schema.pattern );
		if ( ! pattern.test( value ) ) {
			return true;
		}
	}

	return false;
};
