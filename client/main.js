/*global wcConnectData */
var React = require( 'react' );
var ReactDOM = require( 'react-dom' );
var Settings = require( './views/settings' );

document.addEventListener( 'DOMContentLoaded', function() {
	ReactDOM.render(
		React.createElement( Settings, {
			schema: wcConnectData.formSchema,
			layout: wcConnectData.formLayout,
			initialValue: wcConnectData.formData
		} ),
		document.getElementById( 'wc-connect-admin-container' )
	);
} );
