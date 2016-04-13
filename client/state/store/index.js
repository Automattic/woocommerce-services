import { createStore } from 'redux';
import rootReducer from '../reducers';

export default ( initialState ) => {
	const store = createStore(
		rootReducer,
		initialState,
		window.devToolsExtension ? window.devToolsExtension() : f => f
	);

	if ( module.hot ) {
		module.hot.accept( '../reducers', () => {
			const nextRootReducer = require( '../reducers' ).default;
			store.replaceReducer( nextRootReducer );
		} );
	}

	return store;
}
