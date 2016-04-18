
const saveForm = ( url, nonce, formData ) => {
	const request = {
		method: 'PUT',
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
		},
		body: JSON.stringify( formData ),
	};

	return fetch( url, request ).then( response => {
		return response.json();
	} ).catch( () => {
		// TODO: Handler errors
	} );
};

export default saveForm;
