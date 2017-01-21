if ( ! global._babelPolyfill ) {
	require( 'babel-polyfill' );
}

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
import AccountSettings from './account-settings';
import Packages from './packages';
import every from 'lodash/every';

( () => {
	if ( ! global.wcConnectData ) {
		return;
	}

	const Route = ( ( rootView ) => {
		switch ( rootView ) {
			case 'wc-connect-create-shipping-label':
				return ShippingLabel;
			case 'wc-connect-service-settings':
			case 'wc-connect-admin-help':
				return Settings;
			case 'wc-connect-account-settings':
				return AccountSettings;
			case 'wc-connect-packages':
				return Packages;
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
		const state = store.getState();

		if ( ! state.form || ( state.form.meta && state.form.meta.pristine ) || every( state.form.pristine ) ) {
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
			const RedBox = require( 'redbox-react' ).default;
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
				throw error;
			}
		};

		module.hot.accept( [
			'./settings/views',
			'./shipping-label/views',
			'./account-settings/views',
		], () => {
			setTimeout( render );
		} );

		module.hot.accept( [
			'./settings/state/reducer',
			'./shipping-label/state/reducer',
			'./account-settings/state/reducer',
		], () => {
			store.replaceReducer( Route.getHotReducer() );
		} );
	}

	render();
} )();
