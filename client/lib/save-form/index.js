import isArray from 'lodash/isArray';
import { EMPTY_ERROR } from 'state/selectors/errors';

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
				let errors = json.data.data.fields;
				// Some services still give the field errors in an array, keep backwards-compatibility
				if ( isArray( errors ) ) {
					errors = {};
					json.data.data.fields.forEach( ( fieldName ) => errors[ fieldName ] = EMPTY_ERROR );
				}
				return setError( errors );
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
