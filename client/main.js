/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import Settings from './views/usps';

document.addEventListener( 'DOMContentLoaded', () => {
	ReactDOM.render(
		React.createElement( Settings, {
			schema: wcConnectData.formSchema,
			layout: wcConnectData.formLayout,
			initialValue: wcConnectData.formData
		} ),
		document.getElementById( 'wc-connect-admin-container' )
	);
} );
