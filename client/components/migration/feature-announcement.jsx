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
			fetch( getBaseURL() + 'wc-admin/plugins/install', {
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
		const markMigrationDoneAPICall = () =>
			fetch( getBaseURL() + 'wc/v1/connect/migration-flag', {
				method: 'POST',
				headers
			} );


		const installAndActivatePlugins = async() => {
			const tasks = [
				installPluginAPICall,
				activatePluginAPICall,
				markMigrationDoneAPICall,
				// Note: Anything that needs the plugin's code needs to happen before deactivating it.
				deactivateWCSTPluginAPICall,
			];

			try {
				setIsUpdating(true);

				for (const task of tasks) {
					const apiResponse = await task();
					const apiJSONResponse = await apiResponse.json();
					if (task.status >= 400) {
						throw new Error(apiJSONResponse.message || translate("Failed to setup WooCommerce Shipping. Please try again."));
					}
				}

				window.location = global.wcsPluginData.adminPluginPath;
			} catch (e) {
				//TODO: error handling.
				// console.log('Failed to install or activate.', e);
			} finally {
				setIsUpdating(false);
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
