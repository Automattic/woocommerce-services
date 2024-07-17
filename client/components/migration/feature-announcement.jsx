/**
 * External dependencies
 */
import React from 'react';
import { Flex, FlexItem, Modal, Icon, Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import bg from './images/wcshipping-migration.jpg';
import { Dashboard, Preformatted, LessonPlan, Shipping } from './icons';
import {
	isEligableToMigrate,
	wcshippingMigrationState,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import { installAndActivatePlugins } from './migration-runner';
import { TIME_TO_REMMEMBER_DISMISSAL_SECONDS } from './constants';

const FeatureAnnouncement = ( { translate, isEligable, previousMigrationState } ) => {
	const [isOpen, setIsOpen] = useState(isEligable);
	const [isUpdating, setIsUpdating] = useState(false);

	useEffect( () => {
		const isMigrationDismissed = window.wpCookies.get( 'wcst-wcshipping-migration-dismissed' ) && parseInt( window.wpCookies.get( 'wcst-wcshipping-migration-dismissed' ) );
		if ( isMigrationDismissed ) {
			setIsOpen( false );
		}
	}, [] );

	const closeModal = () => {
		setIsOpen(false);
	};

	const snooze = () => {
		window.wpCookies.set( 'wcst-wcshipping-migration-dismissed', 1, TIME_TO_REMMEMBER_DISMISSAL_SECONDS );
		setIsOpen( false );
	};

	const update = async () => {
		setIsUpdating( true );
		await installAndActivatePlugins( previousMigrationState );
		setIsUpdating( false );
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
					<p>{translate('We\'ll ensure a smooth transition by providing the functionality to carry over all your settings and shipping labels when you update.')}</p>
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
					<Button id="migration__announcement-update-button" isPrimary onClick={update} isBusy={isUpdating} disabled={isUpdating}>
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
	</Modal>)}
	</>;

};

const mapStateToProps = (state, { siteId }) => ({
	isEligable: isEligableToMigrate(state, siteId),
	previousMigrationState: wcshippingMigrationState( state, siteId ),
});

const mapDispatchToProps = dispatch => bindActionCreators({}, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(localize(FeatureAnnouncement));
