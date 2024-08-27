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
import { TIME_TO_REMMEMBER_DISMISSAL_SECONDS } from 'components/migration/constants';

const container = document.getElementById('wcst_wcshipping_migration_admin_notice_feature_announcement');
const args = container.dataset.args && JSON.parse( container.dataset.args ) || {};
setNonce( args.nonce );
setBaseURL( args.baseURL );
const ShippingLabelStore = ShippingLabel(args);
const store = createStore(ShippingLabelStore.getReducer(), ShippingLabelStore.getInitialState());

const wcstWCShippingMigrationNoticeButton = document.getElementById('wcst-wcshipping-migration-notice__click');
const wcstMigrationNoticeDimissButton = document.querySelector('.wcst-wcshipping-migration-notice button.notice-dismiss');

// Add all button events
["click", "keydown"].forEach(eventName => {
	// Clicking "Confirm update" will start the migration. This is the same as popping up the modal and clicking the "Update" button there.
	wcstWCShippingMigrationNoticeButton.addEventListener(eventName, (evt) => {
		/**
		 * Prevent form submission when rendered in a form or alike that listens to button click
		 */
		evt.preventDefault();
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
	});

	// Dismiss it for 3 days, then remove it from view.
	wcstMigrationNoticeDimissButton.addEventListener(eventName, () => {
		// window.wpCookies API: wordpress/wp-includes/js/utils.js
		window.wpCookies.set('wcst-wcshipping-migration-dismissed', 1, TIME_TO_REMMEMBER_DISMISSAL_SECONDS)
		const wcstMigrationAdminNotice = document.querySelector('.wcst-wcshipping-migration-notice');
		wcstMigrationAdminNotice.remove();
	});
});
