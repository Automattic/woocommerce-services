let iframe = null;

/**
 * Opens the native printing dialog to print the given URL.
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe.
 * @param {string} url URL of the document to print
 * @param {string} nonce Nonce used to authenticate the request
 * @returns {Promise} Promise that resolves when the document is loaded, and rejected if there's an error fetching it
 */
export default ( url, nonce ) => {
	const request = {
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
		},
	};
	return fetch( url, request )
		.then( ( response ) => {
			if ( 200 !== response.status ) {
				throw new Error( response.statusText );
			}
			return response.blob();
		} )
		.then( URL.createObjectURL )
		.then( ( dataUrl ) => {
			if ( iframe ) {
				document.body.removeChild( iframe );
			}
			iframe = document.createElement( 'iframe' );
			iframe.src = dataUrl;
			iframe.style.display = 'none';
			iframe.onload = () => {
				iframe.contentWindow.print();
			};
			document.body.appendChild( iframe );
		} );
};
