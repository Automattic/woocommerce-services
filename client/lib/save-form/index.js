
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

	console.log( 'request: ', request );
	return fetch( url, request ).then( response => {
		console.log( 'response: ', response );
		return response.json()
	} ).then( json => {
		console.log( 'json response: ', json );
	} ).catch( e => {
		console.log( 'error: ', e );
	} );
};

export default saveForm;
