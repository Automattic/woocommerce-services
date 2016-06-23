import { applyMiddleware, createStore, combineReducers, compose } from 'redux';
import form from './form/reducer';

// from calypso
import notices from 'state/notices/reducer';

const rootReducer = combineReducers( {
	form,
	notices,
} );

const configureStore = ( initialState, thunk ) => {
	const store = createStore(
		rootReducer,
		initialState,
		compose(
			applyMiddleware( thunk ),
			window.devToolsExtension ? window.devToolsExtension() : f => f
		)
	);

	if ( module.hot ) {
		module.hot.accept( [ './form/reducer' ], () => {
			const nextRootReducer = combineReducers( {
				form: require( './form/reducer' ),
				notices: require( 'state/notices/reducer' ),
			} );
			store.replaceReducer( nextRootReducer );
		} );
	}

	return store;
};

export default configureStore;
