/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from 'state';
import '../assets/stylesheets/style.scss';
import initializeState from './lib/initialize-state';
import saveForm from 'lib/save-form';
import './lib/calypso-boot';

const {
	formData,
	formSchema,
	formLayout,
	storeOptions,
	callbackURL,
	nonce,
} = wcConnectData;

const store = configureStore( initializeState( formSchema, formData ) );

const saveFormData = ( setIsSaving, setSuccess, setError, filterStoreOnSave ) => saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, filterStoreOnSave( store ) );

const rootEl = document.getElementById( 'wc-connect-admin-container' );

let render = () => {
	const WCCShippingSettings = require( 'views/shipping' );
	ReactDOM.render(
		<Provider store={ store }>
			<WCCShippingSettings
				storeOptions={ storeOptions }
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

	module.hot.accept( 'views/shipping', () => {
		setTimeout( render );
	} );
}

render();
