/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from 'state/store';
import '../assets/stylesheets/style.scss';
import initializeState from './lib/initialize-state';
import saveForm from './lib/save-form';

const { formData, formSchema, formLayout, wooCommerceSettings, callbackURL, id, instance, nonce } = wcConnectData;

const saveFormData = data => saveForm( callbackURL, id, instance, nonce, data );

const store = configureStore( initializeState( formSchema, formData ) );

const rootEl = document.getElementById( 'wc-connect-admin-container' );

let render = () => {
	const UspsSettings = require( 'views/usps-settings' );
	ReactDOM.render(
		<Provider store={ store }>
			<UspsSettings
				wooCommerceSettings={ wooCommerceSettings }
				schema={ formSchema }
				layout={ formLayout }
				saveFormData={ saveFormData }
			/>
		</Provider>,
		rootEl
	);
};

if ( module.hot ) {
	// Support hot reloading of components
	// and display an overlay for runtime errors
	const renderApp = render;
	const renderError = ( error ) => {
		const RedBox = require( 'redbox-react' );
		ReactDOM.render(
			<RedBox error={ error } />,
			rootEl
		);
	};

	render = () => {
		try {
			renderApp();
		} catch ( error ) {
			renderError( error );
		}
	};

	module.hot.accept( 'views/usps-settings', () => {
		setTimeout( render );
	} );
}

render();
