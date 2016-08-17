/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { applyMiddleware, createStore, compose } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';
import '../assets/stylesheets/style.scss';
import './lib/calypso-boot';
import { translate as __ } from 'lib/mixins/i18n';
import Settings from './settings';
import ShippingLabel from './shipping-label';
import SharedSettings from './shared-settings';

const Route = ( ( rootView ) => {
	switch ( rootView ) {
		case 'wc-connect-create-shipping-label':
			return ShippingLabel;
		case 'wc-connect-service-settings':
		case 'wc-connect-admin-help':
			return Settings;
		case 'wc-connect-shared-settings':
			return SharedSettings;
	}
} )( wcConnectData.rootView )( wcConnectData );

const store = createStore(
	Route.getReducer(),
	Route.getInitialState(),
	compose(
		applyMiddleware( thunk.withExtraArgument( wcConnectData ) ),
		window.devToolsExtension ? window.devToolsExtension() : f => f
	)
);

window.addEventListener( 'beforeunload', ( event ) => {
	if ( store.getState().form && store.getState().form.pristine ) {
		return;
	}
	const text = __( 'You have unsaved changes.' );
	( event || window.event ).returnValue = text;
	return text;
} );

const rootEl = document.getElementById( wcConnectData.rootView );

let render = () => {
	ReactDOM.render(
		<Provider store={ store }>
			<Route.View />
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

	module.hot.accept( [
		'./settings/views',
		'./shipping-label/views',
		'./shared-settings/views',
	], () => {
		setTimeout( render );
	} );

	module.hot.accept( [
		'./settings/state/reducer',
		'./shipping-label/state/reducer',
		'./shared-settings/state/reducer',
	], () => {
		store.replaceReducer( Route.getHotReducer() );
	} );
}

render();
