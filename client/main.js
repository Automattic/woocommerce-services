/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from 'state';
import '../assets/stylesheets/style.scss';
import initializeState from './lib/initialize-state';
import saveForm from './lib/save-form';
import i18n from './lib/mixins/i18n';

const {
	formData,
	formSchema,
	formLayout,
	wooCommerceSettings,
	callbackURL,
	id,
	instance,
	nonce,
} = wcConnectData;

// Initialize i18n
let i18nLocaleStringsObject = {};

if ( window.i18nLocaleStrings ) {
	i18nLocaleStringsObject = window.i18nLocaleStrings;
}

i18n.initialize( i18nLocaleStringsObject );

const saveFormData = ( setIsSaving, setError, data ) => {
	return saveForm( setIsSaving, setError, callbackURL, id, instance, nonce, data );
};

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
				saveForm={ saveFormData }
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
