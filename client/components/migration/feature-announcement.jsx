/**
 * External dependencies
 */
import React from 'react';
import { Flex, FlexItem, Modal, Icon, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { getNonce, getBaseURL } from 'api/request'; // client/api/request.js

/**
 * Internal dependencies
 */
import bg from './images/wcshipping-migration.jpg';
import { Dashboard, Preformatted, LessonPlan, Shipping } from './icons';
import {
	isEligableToMigrate,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const FeatureAnnouncement = ({ translate, isEligable }) => {
	const [isOpen, setIsOpen] = useState(isEligable);
	const [isUpdating, setIsUpdating] = useState(false);

	const closeModal = () => {
		setIsOpen(false);
	};

	const snooze = () => {
		// Todo: implement maybe later
	};

	const update = () => {
		const plugins = 'woocommerce-shipping,woocommerce-tax'; //this needs to be a CSV string.
		const headers = {
			"Content-Type": "application/json",
			'X-WP-Nonce': getNonce()
	   };

		const installPluginAPICall = () =>
			fetch( getBaseURL() + 'wc-admin/plugins/install2', {
				method: 'POST',
				headers,
				body: JSON.stringify({
					plugins
				})
			} );

		const activatePluginAPICall = () =>
			fetch( getBaseURL() + 'wc-admin/plugins/activate', {
				method: 'POST',
				headers,
				body: JSON.stringify({
					plugins
				})
			} );

		const deactivateWCSTPluginAPICall = () =>
			fetch( getBaseURL() + 'wp/v2/plugins/woocommerce-services/woocommerce-services', {
				method: 'POST',
				headers,
				body: JSON.stringify({
					status: 'inactive'
				})
			} );

		const markMigrationStartedAPICall = (migrationState) => () =>
			fetch( getBaseURL() + 'wc/v1/connect/migration-flag', {
				method: 'POST',
				headers,
				body: JSON.stringify({
					migration_state: migrationState // Check WC_Connect_WCST_To_WCShipping_Migration_State_Enum::STARTED
				})
			} );

		const stateErrorHandlingAPICall = async (failedMigrationState) => () => {
			// TODO: Print the error somewhere in an inbox box?
			console.log(failedMigrationState);

			return fetch( getBaseURL() + 'wc/v1/connect/migration-flag', {
				method: 'POST',
				headers,
				body: JSON.stringify({
					migration_state: failedMigrationState // Check WC_Connect_WCST_To_WCShipping_Migration_State_Enum::STARTED
				})
			} );
		};

		/**
		 * A wrapper function to make API calls and check the API status response.
		 * Throw an exception up if the status > 400.
		 *
		 * @param {function} apiFn The API function that uses fetch.
		 * @returns {Object} The API JSON response from fetch.
		 */
		const fetchAPICall = (apiFn) => async () => {
			const apiResponse = await apiFn();
			const apiJSONResponse = await apiResponse.json();
			if (apiResponse.status >= 400) {
				throw new Error(apiJSONResponse.message || translate("Failed to setup WooCommerce Shipping. Please try again."));
			}
			return apiJSONResponse;
		};


		const installAndActivatePlugins = async() => {
			const migrationStateTransitions = {
				stateInit: {
					success: 'stateInstalling',
					fail: 'stateInit',
					callback: fetchAPICall(markMigrationStartedAPICall(2)),
				},
				stateErrorInit: {
					success: 'stateInit',
					fail: 'stateErrorInit',
					callback: stateErrorHandlingAPICall('stateInit'),
				},
				stateInstalling: {
					success: 'stateActivating',
					fail: 'stateErrorInstalling',
					callback: fetchAPICall(installPluginAPICall),
				},
				stateErrorInstalling: {
					success: 'stateInstalling',
					fail: 'stateErrorInstalling',
					callback: stateErrorHandlingAPICall('stateInstalling'),
				},
				stateActivating: {
					success: 'stateDeactivating', // TODO: This should be stateDBMigrating to migrate DB.
					fail: 'stateErrorActivating',
					callback: fetchAPICall(activatePluginAPICall),
				},
				stateErrorActivating: {
					success: 'stateActivating',
					fail: 'stateErrorActivating',
					callback: stateErrorHandlingAPICall('stateActivating'),
				},
				stateDB: {
					success: 's7',
					fail: 'stateErrorDB',
					callback: new Promise((resolve) => resolve({status: 200})), // TODO: DB migration
				},
				stateErrorDB: {
					success: 'stateDBMigrating',
					fail: 'stateErrorDB',
					callback: stateErrorHandlingAPICall('stateDBMigrating'),
				},
				stateDeactivating: {
					success: 'stateDone',
					fail: 'stateErrorDeactivating',
					callback: fetchAPICall(deactivateWCSTPluginAPICall),
				},
				stateErrorDeactivating: {
					success: 'stateDeactivating',
					fail: 'stateErrorDeactivating',
					callback: stateErrorHandlingAPICall('stateDeactivating'),
				},
				stateDone: { // Done state.
					success: null,
					fail: null,
					callback: null,
				}
			}

			const errorStates = ['stateErrorInstalling', 'stateErrorActivating', 'stateErrorDB', 'stateErrorDeactivating'];

			const runNext = async (migrationState) => {
				const currentMigrationState = migrationStateTransitions[migrationState];
				let nextMigrationStateToRun = '';
				try {
					setIsUpdating(true);
					await currentMigrationState.callback();
					nextMigrationStateToRun = currentMigrationState.success;
				} catch (e) {
					nextMigrationStateToRun = currentMigrationState.fail;
				} finally {
					setIsUpdating(false);
					return nextMigrationStateToRun;
				}
			};

			// Run the migration chain
			let nextMigrationStateToRun = 'stateInit';
			let runAttemps = 0;
			const maxAttempts = Object.keys(migrationStateTransitions).length; // The states don't loop, thus it can't be more than its size. Serve as a safe guard in case of infinite loop.
			while ( runAttemps++ < maxAttempts && nextMigrationStateToRun !== 'stateDone' && ! errorStates.includes(nextMigrationStateToRun)) {
				nextMigrationStateToRun = await runNext(nextMigrationStateToRun);
				console.log(runAttemps);
			}
		};

		installAndActivatePlugins();
	};

	return <>{isOpen && (<Modal
		className="migration__announcement-modal"
		shouldCloseOnEsc={false}
		shouldCloseOnClickOutside={false}
		onRequestClose={closeModal}
	>
		<Flex gap={8}>
			<FlexItem>
				<Flex>
					<span>
						{translate('Update')}
					</span>
					<h2>
						{translate('A new dedicated WooCommerce Shipping extension is now available')}
					</h2>
					<p>{translate(
						'WooCommerce Shipping and WooCommerce Tax are now two dedicated extensions. We\'ll automatically deactivate WooCommerce Shipping & Tax and carry over your settings when you update.')}</p>
					<p>{translate('Here\'s what you can expect from the new shipping experience:')}</p>

					<ul>
						<li>
							<Icon icon={Dashboard}/>
							<div>
								<h3>
									{translate('A seamless transition')}
								</h3>
								<p>
									{translate('All of your settings and shipment history have been imported to the new extension.')}
								</p>
							</div>
						</li>
						<li>
							<Icon icon={Preformatted}/>
							<div>
								<h3>
									{translate('Print and save')}
								</h3>
								<p>
									{translate(
										'Speed up label creation with a streamlined process to print and save your label preferences.')}
								</p>
							</div>
						</li>
						<li>
							<Icon icon={Shipping}/>
							<div>
								<h3>
									{translate('USPS and DHL Express')}
								</h3>
								<p>
									{translate(
										'Send using trusted shipping carriers like USPS and DHL Express, with more options and carriers coming soon.')}
								</p>
							</div>
						</li>

						<li>
							<Icon icon={LessonPlan}/>
							<div>
								<h3>
									{translate('Enhanced label purchase flow')}
								</h3>
								<p>
									{translate('Experience a smoother label purchasing process with our updated interface.')}
								</p>
							</div>
						</li>
					</ul>
				</Flex>
				<Flex>
					{!isUpdating && <Button isTertiary onClick={snooze}>
						{translate('Maybe later')}
					</Button>}
					<Button isPrimary onClick={update} isBusy={isUpdating} disabled={isUpdating}>
						{isUpdating ? translate('Updating') : translate('Update now')}
					</Button>
				</Flex>
			</FlexItem>
			<FlexItem
				style={{ background: `url(${bg}) no-repeat center center`, backgroundSize: 'contain' }}
			>
				&nbsp;
			</FlexItem>
		</Flex>
	</Modal>)};
	</>;

};

const mapStateToProps = (state, { siteId }) => ({
	isEligable: isEligableToMigrate(state, siteId),
});

const mapDispatchToProps = dispatch => bindActionCreators({}, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(localize(FeatureAnnouncement));
