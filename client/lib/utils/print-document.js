/**
 * Opens the native printing dialog to print the given URL.
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe. After that, the iframe is removed.
 * @param {string} url URL of the document to print
 * @param {string} nonce
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
			return response.blob().then( ( blob ) => URL.createObjectURL( blob ) );
		} )
		.then( ( dataUrl ) => {
			const iframe = document.createElement( 'iframe' );
			iframe.src = dataUrl;
			iframe.style.display = 'none';
			iframe.onload = () => {
				iframe.contentWindow.print();
				setTimeout( () => document.body.removeChild( iframe ), 1000 );
			};
			document.body.appendChild( iframe );
		} );
};
