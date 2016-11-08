let iframe = null;

/**
 * Opens the native printing dialog to print the given URL.
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe.
 * @param {string} url URL of the document to print
 * @returns {Promise} Promise that resolves when the document is loaded, and rejected if there's an error fetching it
 */
export default ( url ) => {
	return new Promise( ( resolve, reject ) => {
		if ( iframe ) {
			document.body.removeChild( iframe );
		}
		iframe = document.createElement( 'iframe' );
		iframe.src = url;
		iframe.style.display = 'none';
		iframe.onload = () => {
			iframe.contentWindow.print();
			resolve();
		};
		iframe.onerror = ( error ) => {
			reject( error );
		};
		document.body.appendChild( iframe );
	} );
};
