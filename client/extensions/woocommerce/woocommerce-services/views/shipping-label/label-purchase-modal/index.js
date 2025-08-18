/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddressStep from './address-step';
import PackagesStep from './packages-step';
import CustomsStep from './customs-step';
import RatesStep from './rates-step';
import Sidebar from './sidebar';
import MigrationSurveyModal from 'components/migration-survey/modal';
import { exitPrintingFlow } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel, isLoaded, isCustomsFormRequired,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import FeatureAnnouncement from 'components/migration/feature-announcement';

const LabelPurchaseModal = props => {
	const { loaded, translate, showPurchaseDialog } = props;
	const [ showSurveyModal, setShowSurveyModal ] = React.useState( false );
	const [ dialogWasShown, setDialogWasShown ] = React.useState( false );

	if ( !loaded ) {
		return null;
	}

	// Track when dialog is shown
	React.useEffect( () => {
		if ( showPurchaseDialog ) {
			setDialogWasShown( true );
		}
	}, [ showPurchaseDialog ] );

	// Listen for successful label purchase/print completion
	React.useEffect( () => {
		const handleSurveyTrigger = () => {
			const shouldShow = window.wcsMigrationSurvey && window.wcsMigrationSurvey.shouldShow;
			const isForced = new URLSearchParams(window.location.search).get('force_survey') !== null;
			
			// Show survey if conditions are met and dialog was shown (or forced)
			if ( shouldShow && (dialogWasShown || isForced) ) {
				setTimeout( () => {
					setShowSurveyModal( true );
				}, 500 ); // Small delay to ensure print dialogs have closed
			}
		};

		// Listen for custom event that we'll dispatch on successful completion
		window.addEventListener('wcs-label-purchase-completed', handleSurveyTrigger);
		
		return () => {
			window.removeEventListener('wcs-label-purchase-completed', handleSurveyTrigger);
		};
	}, [ dialogWasShown ] );

	// Handle force_survey - simulate successful purchase when modal closes
	React.useEffect( () => {
		const isForced = new URLSearchParams(window.location.search).get('force_survey') !== null;
		
		if ( isForced && !showPurchaseDialog && dialogWasShown ) {
			// Dialog was shown and now closed with force_survey - simulate successful completion
			window.dispatchEvent(new CustomEvent('wcs-label-purchase-completed'));
		}
	}, [ showPurchaseDialog, dialogWasShown ] );

	const onClose = () => {
		props.exitPrintingFlow(props.orderId, props.siteId, false);
	};


	return (<>
		{ showPurchaseDialog && (
			<Modal
				className="woocommerce label-purchase-modal wcc-root"
				shouldCloseOnClickOutside={false}
				onRequestClose={onClose}
				title={translate('Create shipping label',
					'Create shipping labels',
					{ count: Object.keys(props.form.packages.selected).length }
				)}
			>
				<div className="label-purchase-modal__content">
					<div className="label-purchase-modal__main-section">
						<AddressStep
							type="origin"
							title={translate('Origin address')}
							siteId={props.siteId}
							orderId={props.orderId}
						/>
						<AddressStep
							type="destination"
							title={translate('Destination address')}
							siteId={props.siteId}
							orderId={props.orderId}
						/>
						<PackagesStep siteId={props.siteId} orderId={props.orderId}/>
						{props.isCustomsFormRequired && (<CustomsStep siteId={props.siteId} orderId={props.orderId}/>)}
						<RatesStep siteId={props.siteId} orderId={props.orderId}/>
					</div>
					<Sidebar siteId={props.siteId} orderId={props.orderId}/>
				</div>
			</Modal>
		) }
		<FeatureAnnouncement siteId={props.siteId} orderId={props.orderId}/>
		{ showSurveyModal && (
			<MigrationSurveyModal
				isVisible={ showSurveyModal }
				onClose={ () => setShowSurveyModal( false ) }
			/>
		) }
	</>);
};

LabelPurchaseModal.propTypes = {
	siteId: PropTypes.number.isRequired, orderId: PropTypes.number.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		loaded,
		form: loaded && shippingLabel.form,
		showPurchaseDialog: shippingLabel.showPurchaseDialog,
		isCustomsFormRequired: isCustomsFormRequired( state, orderId, siteId ),
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators( { exitPrintingFlow }, dispatch );
};

export default connect(
	mapStateToProps, mapDispatchToProps
)( localize( LabelPurchaseModal ) );
