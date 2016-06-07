/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from 'state';
import '../assets/stylesheets/style.scss';
import initializeState from './lib/initialize-state';
import { successNotice, errorNotice } from 'state/notices/actions';
import isString from 'lodash/isString';
import isArray from 'lodash/isArray';
import { translate as __ } from 'lib/mixins/i18n';
import { setField } from 'state/form/actions.js';
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

const onSaving = ( value ) => store.dispatch(
	setField( 'isSaving', value )
);

const onSuccess = ( value ) => {
	if ( true === value ) {
		store.dispatch(
			successNotice( __( 'Your changes have been saved.' ), { duration: 2250 } )
		);
	}
};

const onError = ( value ) => {
	if ( isString( value ) ) {
		store.dispatch(
			errorNotice(
				value,
				{ duration: 7000 }
			)
		);
	}

	if ( isArray( value ) ) {
		store.dispatch(
			errorNotice(
				__( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ),
				{ duration: 7000 }
			)
		);
	}
};

const onSaveForm = () => saveForm( onSaving, onSuccess, onError, callbackURL, nonce, store );

const rootEl = document.getElementById( 'wc-connect-admin-container' );

let render = () => {
	const WCCShippingSettings = require( 'views/shipping' );
	ReactDOM.render(
		<Provider store={ store }>
			<WCCShippingSettings
				storeOptions={ storeOptions }
				schema={ formSchema }
				layout={ formLayout }
				saveForm={ onSaveForm }
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
