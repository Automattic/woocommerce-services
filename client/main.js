/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from './store';
import '../assets/stylesheets/style.scss';

const store = configureStore()
const rootEl = document.getElementById( 'wc-connect-admin-container' )

let render = () => {
	const Settings = require( './views/usps' );
	ReactDOM.render(
		<Provider store={ store }>
			<Settings
				schema={ wcConnectData.formSchema }
				layout={ wcConnectData.formLayout }
				initialValue={ wcConnectData.formData }
			/>
		</Provider>,
		rootEl
	)
}

if (module.hot) {
	// Support hot reloading of components
	// and display an overlay for runtime errors
	const renderApp = render
	const renderError = ( error ) => {
		const RedBox = require( 'redbox-react' );
		ReactDOM.render(
			<RedBox error={error} />,
			rootEl
		);
	};

	render = () => {
		try {
			renderApp();
		} catch ( error ) {
			renderError( error );
		}
	}
	module.hot.accept( './views/usps', () => {
		setTimeout( render );
	})
}

render();
