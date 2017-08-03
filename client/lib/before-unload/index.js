const handlers = [];

const attachMainHandler = () => {
	window.onbeforeunload = ( event ) => {
		const messages = handlers.map( ( handler ) => handler() ).filter( Boolean );
		if ( ! messages.length ) {
			return;
		}
		const text = messages.join( '\n' );
		( event || window.event ).returnValue = text;
		return text;
	};
};

/**
 * Adds a handler to be executed before the page will be unloaded (due to the user closing it, navigating away or reloading).
 * @param {Function} handler Function to be executed. Needs to return a string to be displayed in a confirmation dialog,
 * or a "false-y" value if the page can be unloaded safely without losing data.
 */
export default ( handler ) => {
	if ( ! window.onbeforeunload ) {
		attachMainHandler();
	}
	handlers.push( handler );
};
