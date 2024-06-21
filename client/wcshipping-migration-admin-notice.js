/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { createStore } from 'redux';

/**
 * Internal dependencies
 */
import '../assets/stylesheets/migration_to_wcshipping_admin_notice.scss';
import FeatureAnnouncement from 'components/migration/feature-announcement';
import ShippingLabel from 'wcs-client/apps/shipping-label';

const container = document.getElementById('wcst_wcshipping_migration_admin_notice_feature_announcement');
const args = container.dataset.args && JSON.parse( container.dataset.args ) || {};
const ShippingLabelStore = ShippingLabel(args);
const store = createStore(ShippingLabelStore.getReducer(), ShippingLabelStore.getInitialState());

 ReactDOM.render(
 	<Provider store={store}>
		<FeatureAnnouncement />
	 </Provider>,
	container
);