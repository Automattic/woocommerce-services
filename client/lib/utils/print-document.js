import pdfSupport from 'lib/utils/pdf-support';

let iframe = null;

/**
 * Loads the given URL in an invisible <iframe>
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe.
 * @param {string} url URL to load
 * @returns {Promise} Promise that resolves when the iframe finished loading, rejects on error
 */
const loadDocumentInFrame = ( url ) => {
	return new Promise( ( resolve, reject ) => {
		if ( iframe ) {
			document.body.removeChild( iframe );
		}
		iframe = document.createElement( 'iframe' );
		iframe.src = url;

		// Note: Don't change this for "display: none" or it will stop working on MS Edge
		iframe.style.position = 'fixed';
		iframe.style.left = '-999px';

		iframe.onload = () => {
			resolve();
		};
		iframe.onerror = ( error ) => {
			reject( error );
		};

		document.body.appendChild( iframe );
	} );
};

/**
 * Opens the native printing dialog to print the given URL.
 * Falls back to opening the PDF in a new tab if opening the printing dialog is not supported.
 * @param {string} url URL of the document to print
 * @param {boolean} isUserInteraction True if this code is being executed as a result of a direct user interaction,
 * such as the click of a button. False otherwise (if this was triggered by an AJAX response, for example
 * @returns {Promise} Promise that resolves with true if the printing dialog (or equivalent) was correctly
 * invoked. It resolves with false if it's required to call this function again as a result of direct user interaction.
 */
export default ( url, isUserInteraction ) => {
	switch ( pdfSupport() ) {
		case 'native':
			// Happy case where everything can happen automatically. Supported in Edge, Chrome and Safari
			return loadDocumentInFrame( url )
				.then( () => {
					iframe.contentWindow.print();
					return true;
				} );

		case 'addon':
			// window.open will be blocked by the browser if this code isn't being executed from a direct user interaction
			if ( isUserInteraction ) {
				window.open( url );
				return Promise.resolve( true );
			}
			return Promise.resolve( false );

		default:
			// If browser doesn't support PDFs at all, this will trigger the "Download" pop-up.
			// No need to wait for the iframe to load, it will never finish.
			loadDocumentInFrame( url );
			return Promise.resolve( true );
	}
};
