/**
 * Opens the native printing dialog to print the given URL.
 * To do that, an invisible <iframe> is created, added to the current page, and "print()" is invoked
 * for just that iframe. After that, the iframe is removed.
 * @param {string} url URL of the document to print
 */
export default ( url ) => {
	const iframe = document.createElement( 'iframe' );
	iframe.src = url;
	iframe.style.display = 'none';
	iframe.onload = () => {
		iframe.contentWindow.print();
		setTimeout( () => document.body.removeChild( iframe ), 1000 );
	};
	document.body.appendChild( iframe );
};
