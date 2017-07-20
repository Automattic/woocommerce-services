/**
 * External dependencies
 */
import 'babel-polyfill';
import React from 'react';
import ReactDOM from 'react-dom';
import { applyMiddleware, createStore, compose } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import '../assets/stylesheets/style.scss';
import './lib/calypso-boot';
import * as storageUtils from 'lib/utils/local-storage';
import Settings from './apps/settings';
import ShippingLabel from './apps/shipping-label';
import AccountSettings from './apps/account-settings';
import PrintTestLabel from './apps/print-test-label';
import Packages from './apps/packages';
import { setNonce, setBaseURL } from 'api/request';

if ( global.wcConnectData ) {
	setNonce( global.wcConnectData.nonce );
	setBaseURL( global.wcConnectData.baseURL );
}

const getRouteClass = ( classNames ) => {
	for ( let i = 0; i < classNames.length; i++ ) {
		switch ( classNames[ i ] ) {
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
	}
	return null;
};

Array.from( document.getElementsByClassName( 'wcc-root' ) ).forEach( ( container ) => {
	const args = JSON.parse( container.dataset.args ) || {};
	delete container.dataset.args;

	const RouteClass = getRouteClass( container.classList );
	if ( ! RouteClass ) {
		return;
	}
	const Route = RouteClass( args );

	const persistedStateKey = Route.getStateKey();
	const persistedState = storageUtils.getWithExpiry( persistedStateKey );
	storageUtils.remove( persistedStateKey );
	const serverState = Route.getInitialState();

	const store = createStore(
		Route.getReducer(),
		{ ...serverState, ...persistedState },
		compose(
			applyMiddleware( thunk.withExtraArgument( args ) ),
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

	let render = () => {
		ReactDOM.render(
			<Provider store={ store }>
				<Route.View />
			</Provider>,
			container
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
				container
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
			'./apps/settings/view',
			'./apps/shipping-label/view',
			'./apps/account-settings/view',
		], () => {
			setTimeout( render );
		} );

		module.hot.accept( [
			'./apps/settings/state/reducer',
			'./apps/shipping-label/state/reducer',
			'./apps/account-settings/state/reducer',
		], () => {
			store.replaceReducer( Route.getHotReducer() );
		} );
	}

	render();
} );
