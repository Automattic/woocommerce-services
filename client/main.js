/**
 * External dependencies
 */
import '@babel/polyfill';
import React from 'react';
import ReactDOM from 'react-dom';
import ReactModal from 'react-modal';
import { applyMiddleware, createStore, compose } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';

/**
 * Internal dependencies
 */
import '../assets/stylesheets/style.scss';
import './lib/calypso-boot';
import * as storageUtils from 'lib/utils/local-storage';
import Settings from './apps/settings';
import ShippingLabel from './apps/shipping-label';
import ShippingSettings from './apps/shipping-settings';
import PrintTestLabel from './apps/print-test-label';
import PluginStatus from './apps/plugin-status';
import StripeConnectAccount from './apps/stripe-connect-account';
import { setNonce, setBaseURL } from 'api/request';
import wpcomApiMiddleware from 'state/data-layer/wpcom-api-middleware';
import localApiMiddleware from 'lib/local-api-middleware';

if ( global.wcConnectData ) {
	setNonce( global.wcConnectData.nonce );
	setBaseURL( global.wcConnectData.baseURL );
}

const getRouteClassName = ( classNames ) => {
	for ( let i = 0; i < classNames.length; i++ ) {
		switch ( classNames[ i ] ) {
			case 'wc-connect-create-shipping-label':
			case 'wc-connect-service-settings':
			case 'wc-connect-admin-status':
			case 'wc-connect-shipping-settings':
			case 'wc-connect-admin-test-print':
			case 'wc-connect-stripe-connect-account':
				return classNames[ i ];
		}
	}
	return null;
};

const getRouteClass = ( className ) => {
	switch ( className) {
		case 'wc-connect-create-shipping-label':
			return ShippingLabel;
		case 'wc-connect-service-settings':
			return Settings;
		case 'wc-connect-admin-status':
			return PluginStatus;
		case 'wc-connect-shipping-settings':
			return ShippingSettings;
		case 'wc-connect-admin-test-print':
			return PrintTestLabel;
		case 'wc-connect-stripe-connect-account':
			return StripeConnectAccount;
		default:
			return null;
	}
};

const createdStores = {};

Array.from( document.getElementsByClassName( 'wcc-root' ) ).forEach( ( container ) => {
	const args = container.dataset.args && JSON.parse( container.dataset.args ) || {};
	delete container.dataset.args;
	const routeClassName = getRouteClassName( container.classList );

	const RouteClass = getRouteClass( routeClassName );
	if ( ! RouteClass ) {
		return;
	}
	const Route = RouteClass( args );

	if( typeof createdStores[ routeClassName ] === 'undefined' || routeClassName !== 'wc-connect-create-shipping-label' ) {
		const persistedStateKey = Route.getStateKey();
		const persistedState = storageUtils.getWithExpiry( persistedStateKey );
		storageUtils.remove( persistedStateKey );
		const serverState = Route.getInitialState();
		const initialState = { ...serverState, ...persistedState };

		const middlewares = [
			thunk.withExtraArgument( args ),
			wpcomApiMiddleware,
			localApiMiddleware,
		];

		if ( Route.getMiddlewares ) {
			middlewares.push.apply( middlewares, Route.getMiddlewares() );
		}

		const enhancers = [
			applyMiddleware( ...middlewares ),
			window.devToolsExtension && window.devToolsExtension(),
		].filter( Boolean );

		const store = compose( ...enhancers )( createStore )( Route.getReducer(), initialState );

		if ( Route.getInitialActions ) {
			Route.getInitialActions().forEach( store.dispatch );
		}

		window.addEventListener( 'beforeunload', () => {
			const state = store.getState();

			if ( window.persistState ) {
				storageUtils.setWithExpiry( persistedStateKey, Route.getStateForPersisting( state ) );
			}
		} );

		createdStores[ routeClassName ] = store;
	}

	ReactModal.setAppElement( container );
	ReactDOM.render(
		<Provider store={ createdStores[ routeClassName ] }>
			<Route.View />
		</Provider>,
		container
	);
} );
