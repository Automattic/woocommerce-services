import _ from 'lodash';
import { translate as __ } from 'lib/mixins/i18n';
import parseJson from 'lib/utils/parse-json';
import { EMPTY_ERROR } from 'settings/state/selectors/errors';

const saveForm = ( setIsSaving, setSuccess, setFieldsStatus, setError, url, nonce, submitMethod, formData ) => {
	setIsSaving( true );
	const request = {
		method: submitMethod || 'POST',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
			'Content-Type': 'application/json',
		},
	};
	if ( formData ) {
		request.body = JSON.stringify( formData );
	}

	return fetch( url, request ).then( ( response ) => {
		setError( null, null );
		setSuccess( false );

		return parseJson( response ).then( ( json ) => {
			if ( json.success ) {
				return setSuccess( true, json );
			}

			if ( 'rest_cookie_invalid_nonce' === json.code && window.localStorage ) {
				setError( json.code );
				window.persistState = true;
				alert( __( 'There was a problem saving your settings. Please try again after the page is reloaded.' ) );
				location.reload();
				return;
			}

			if ( 'validation_failure' === _.get( json, 'data.error' ) && _.get( json, 'data.data.fields' ) ) {
				let fieldsStatus = json.data.data.fields;
				// Some services still give the field errors in an array, keep backwards-compatibility
				if ( _.isArray( fieldsStatus ) ) {
					fieldsStatus = {};
					json.data.data.fields.forEach( ( fieldName ) => fieldsStatus[ fieldName ] = EMPTY_ERROR );
				}
				return setFieldsStatus( fieldsStatus );
			}

			if ( json.data.message ) {
				return setError( json.data.message, json );
			}

			return setError( JSON.stringify( json ), json );
		} ).then( () => setIsSaving( false ) );
	} ).catch( ( e ) => {
		setError( e );
		setIsSaving( false );
	} );
};

export default saveForm;
