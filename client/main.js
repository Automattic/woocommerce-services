/*global wcConnectData */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux'
import configureStore from 'state/store';
import UspsSettings from 'views/usps-settings';
import '../assets/stylesheets/style.scss';

// Temporarily add dummy data
wcConnectData.formData.services = [
	{
		id: 'FIRST_CLASS_MAIL',
		group: 'First-Class',
		title: 'First-Class Mail',
		enabled: true,
		adjustment: 11,
		adjustment_type: 'flat'
	},
	{
		id: 'FIRST_CLASS_INTERNATIONAL_LETTERS',
		group: 'First-Class',
		title: 'First-Class Mail International Letters',
		enabled: true,
		adjustment: 2,
		adjustment_type: 'flat'
	},
	{
		id: 'GLOBAL_EXPRESS_GUARANTEED',
		group: 'Global Express',
		title: 'Global Express Guaranteed',
		enabled: true,
		adjustment: 3,
		adjustment_type: 'flat'
	},
	{
		id: 'INTERNATIONAL_POSTCARDS',
		group: 'Other Services',
		title: 'International Postcards',
		enabled: true,
		adjustment: 4,
		adjustment_type: 'flat'
	},
	{
		id: 'LIBRARY_MAIL',
		group: 'Other Services',
		title: 'Library Mail',
		enabled: true,
		adjustment: 10,
		adjustment_type: 'percentage'
	},
	{
		id: 'MEDIA_MAIL',
		group: 'Other Services',
		title: 'Media Mail',
		enabled: true,
		adjustment: 20,
		adjustment_type: 'percentage'
	},
	{
		id: 'PRIORITY_MAIL',
		group: 'Priority Mail ',
		title: 'Priority Mail',
		enabled: true,
		adjustment: 30,
		adjustment_type: 'percentage'
	},
	{
		id: 'PRIORITY_MAIL_INTERNATIONAL',
		group: 'Priority Mail ',
		title: 'Priority Mail International',
		enabled: true,
		adjustment: 3,
		adjustment_type: 'flat'
	},
	{
		id: 'PRIORITY_MAIL_EXPRESS',
		group: 'Priority Mail Express',
		title: 'Priority Mail Express',
		enabled: true,
		adjustment: 1.5,
		adjustment_type: 'flat'
	},
	{
		id: 'PRIORITY_MAIL_EXPRESS_FLAT_RATE',
		group: 'Priority Mail Express',
		title: 'Priority Mail Express Flat Rate',
		enabled: true,
		adjustment: 1.5,
		adjustment_type: 'flat'
	},
	{
		id: 'PRIORITY_MAIL_EXPRESS_INTERNATIONAL',
		group: 'Priority Mail Express',
		title: 'Priority Mail Express International',
		enabled: true,
		adjustment: 1.5,
		adjustment_type: 'flat'
	},
	{
		id: 'STANDARD_POST',
		group: 'Other Services',
		title: 'Standard Post',
		enabled: true,
		adjustment: 20,
		adjustment_type: 'percentage'
	},
];

const store = configureStore( {
	settings: wcConnectData.formData
} );

const rootEl = document.getElementById( 'wc-connect-admin-container' );

let render = () => {
	ReactDOM.render(
		<Provider store={ store }>
			<UspsSettings
				wooCommerceSettings={ wcConnectData.wooCommerceSettings }
				schema={ wcConnectData.formSchema }
				layout={ wcConnectData.formLayout }
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

	module.hot.accept( 'views/usps-settings', () => {
		setTimeout( render );
	} );
}

render();
