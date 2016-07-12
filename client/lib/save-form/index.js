const saveForm = ( setIsSaving, setSuccess, setError, url, nonce, submitMethod, formData ) => {
	setIsSaving( true );
	const request = {
		method: submitMethod || 'PUT',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
		},
		body: JSON.stringify( formData ),
	};

	return fetch( url, request ).then( response => {
		setError( null );
		setSuccess( false );

		return response.json().then( json => {
			if ( json.success ) {
				return setSuccess( true );
			}

			if ( json.data &&
				'validation_failure' === json.data.error &&
				json.data.data &&
				json.data.data.fields
			) {
				if ( json.data.data.fields ) {
					return setError( json.data.data.fields );
				}
			}

			if ( json.data.message ) {
				return setError( json.data.message );
			}

			return setError( JSON.stringify( json ) );
		} ).then( () => setIsSaving( false ) );
	} ).catch( ( e ) => {
		setIsSaving( false );
		setError( e );
	} );
};

export default saveForm;
