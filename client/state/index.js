import { createStore, combineReducers } from 'redux';
import settings from './settings/reducer';
import form from './form/reducer';

const rootReducer = combineReducers( {
	settings,
	form,
} );

const configureStore = ( initialState ) => {
	const store = createStore(
		rootReducer,
		initialState,
		window.devToolsExtension ? window.devToolsExtension() : f => f
	);

	if ( module.hot ) {
		module.hot.accept( [ './settings/reducer', './form/reducer' ], () => {
			const nextRootReducer = combineReducers( {
				settings: require( './settings/reducer' ),
				form: require( './form/reducer' ),
			} );
			store.replaceReducer( nextRootReducer );
		} );
	}

	return store;
};

export default configureStore;
