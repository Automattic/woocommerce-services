import browserPDFSupport from 'browser-pdf-support';

let iframe = null;

const canPrintProgramatically = () => {
	return navigator.mimeTypes[ 'application/pdf' ]
		&& ! navigator.userAgent.includes( 'Firefox' );
};

const loadDocumentInFrame = ( url ) => {
	return new Promise( ( resolve, reject ) => {
		if ( iframe ) {
			document.body.removeChild( iframe );
		}
		iframe = document.createElement( 'iframe' );
		iframe.src = url;
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
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe.
 * @param {string} url URL of the document to print
 * @returns {Promise} Promise that resolves when the document is loaded, and rejected if there's an error fetching it
 */
export default ( url, isUserInteraction ) => {
	if ( ! browserPDFSupport() ) {
		loadDocumentInFrame( url );
		return Promise.resolve( true );
	}

	if ( ! canPrintProgramatically() ) {
		if ( isUserInteraction ) {
			window.open( url );
			return Promise.resolve( true );
		}
		return Promise.resolve( false );
	}

	return loadDocumentInFrame( url ).then( () => {
		iframe.contentWindow.print();
		return true;
	} );
};
