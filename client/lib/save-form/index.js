
const saveForm = ( url, id, instance, nonce, formData ) => {
	const body = new FormData();
	body.append( 'nonce', nonce );
	body.append( 'id', id );
	body.append( 'instance', instance );
	body.append( 'settings', JSON.stringify( formData ) );

	const request = {
		method: 'POST',
		credentials: 'same-origin',
		body,
	};

	return fetch( url, request ).then( response => {
		return response.json();
	} ).then( json => {
		alert( 'server response: ' + JSON.stringify( json ) )
	} ).catch( () => {
		// TODO: Handler errors
	} );
};

export default saveForm;
