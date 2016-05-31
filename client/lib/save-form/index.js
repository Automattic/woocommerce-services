const saveForm = ( setIsSaving, setSuccess, setError, url, nonce, formData ) => {
	setIsSaving( true );
	const request = {
		method: 'PUT',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
		},
		body: JSON.stringify( formData ),
	};

	return fetch( url, request ).then( response => {
		setIsSaving( false );
		setError( null );
		setSuccess( false );

		return response.json().then( json => {
			if ( json.success ) {
				return setSuccess( true );
			}

			if ( json.data && 'validation_failure' === json.data.error ) {
				return setError( json.data.data.fields );
			}

			return setError( JSON.stringify( json ) )
		} );
	} ).catch( ( e ) => {
		setIsSaving( false );
		setError( e );
	} );
};

export default saveForm;
