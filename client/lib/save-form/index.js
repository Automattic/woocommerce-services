
const saveForm = ( setIsSaving, setError, url, id, instance, nonce, formData ) => {
	setIsSaving( true );
	const body = new FormData();
	body.append( 'nonce', nonce );
	body.append( 'id', id );
	body.append( 'instance', instance );
	body.append( 'settings', JSON.stringify( formData ) );

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
		if ( response.ok ) {
			return response.json().then( json => {
				if ( ! json.success ) {
					return setError( json.data );
				}

				setError( null );
			} );
		}

		setError( 'Network Error' );
	} ).catch( ( e ) => {
		setIsSaving( false );
		setError( 'Unexpected error: ' + JSON.stringify( e ) );
	} );
};

export default saveForm;
