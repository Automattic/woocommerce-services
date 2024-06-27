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
import { setNonce, setBaseURL } from 'wcs-client/api/request';

const container = document.getElementById('wcst_wcshipping_migration_admin_notice_feature_announcement');
const args = container.dataset.args && JSON.parse( container.dataset.args ) || {};
setNonce( args.nonce );
setBaseURL( args.baseURL );
const ShippingLabelStore = ShippingLabel(args);
const store = createStore(ShippingLabelStore.getReducer(), ShippingLabelStore.getInitialState());

const wcstWCShippingMigrationNoticeButton = document.getElementById('wcst-wcshipping-migration-notice__click');

// Clicking "Confirm update" will start the migration. This is the same as popping up the modal and clicking the "Update" button there.
["click", "keydown"].forEach(eventName =>
	wcstWCShippingMigrationNoticeButton.addEventListener(eventName, () => {
		// Pop open feature announcement modal.
		ReactDOM.render(
			<Provider store={store}>
				<FeatureAnnouncement />
			</Provider>,
			container
		);

		// Click the update button in the modal to start the migration.
		const update_button = document.getElementById('migration__announcement-update-button');
		update_button.click();
	})
);

// This handles the dimiss button.
const TIME_TO_REMMEMBER_DISMISSAL_SECONDS = 10; // 3 Days - number of seconds
( function ( $ ) {
	$( '.wcst-wcshipping-migration-notice' ).on( 'click', '.notice-dismiss', () => {
		window.wpCookies.set( window.wcst_wcshipping_migration_admin_notice.dismissalCookieKey, 1, TIME_TO_REMMEMBER_DISMISSAL_SECONDS );
	} );
})( window.jQuery );
