import 'babel-polyfill';
import React from 'react';
import ReactDOM from 'react-dom';
import { applyMiddleware, createStore, compose } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';
import '../assets/stylesheets/style.scss';
import './lib/calypso-boot';
import { translate as __ } from 'lib/mixins/i18n';
import * as storageUtils from 'lib/utils/local-storage';
import Settings from './settings';
import ShippingLabel from './shipping-label';
import AccountSettings from './account-settings';
import PrintTestLabel from './print-test-label';
import Packages from './packages';
import _ from 'lodash';
import { setNonce, setBaseURL } from 'api/request';

( global.wcConnectData || [] ).forEach( ( wcConnectData ) => {
	setNonce( wcConnectData.nonce );
	setBaseURL( wcConnectData.baseURL );

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
			case 'wc-connect-admin-test-print':
				return PrintTestLabel;
		}
	} )( wcConnectData.rootView )( wcConnectData );

	const persistedStateKey = Route.getStateKey();
	const persistedState = storageUtils.getWithExpiry( persistedStateKey );
	storageUtils.remove( persistedStateKey );
	const serverState = Route.getInitialState();

	const store = createStore(
		Route.getReducer(),
		{ ...serverState, ...persistedState },
		compose(
			applyMiddleware( thunk.withExtraArgument( wcConnectData ) ),
			window.devToolsExtension ? window.devToolsExtension() : f => f
		)
	);
	if ( Route.getInitialAction ) {
		store.dispatch( Route.getInitialAction() );
	}

	window.addEventListener( 'beforeunload', ( event ) => {
		const state = store.getState();

		if ( window.persistState ) {
			storageUtils.setWithExpiry( persistedStateKey, Route.getStateForPersisting( state ) );
			return;
		}

		if ( ! state.form || ( state.form.meta && state.form.meta.pristine ) || _.every( state.form.pristine ) ) {
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
} );
