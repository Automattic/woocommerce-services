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
	const [ purchaseDialogWasShown, setPurchaseDialogWasShown ] = React.useState( false );

	if ( !loaded ) {
		return null;
	}

	// Track when purchase dialog is shown
	React.useEffect( () => {
		if ( loaded && showPurchaseDialog ) {
			setPurchaseDialogWasShown( true );
		}
	}, [ loaded, showPurchaseDialog ] );

	// Show survey when purchase dialog is closed (after it was actually shown)
	React.useEffect( () => {
		// Only show survey if purchase dialog was previously shown AND is now closed
		if ( loaded && 
			 purchaseDialogWasShown && 
			 !showPurchaseDialog && 
			 window.wcsMigrationSurvey && 
			 window.wcsMigrationSurvey.shouldShow ) {
			
			// Add a small delay to ensure any print dialogs have also closed
			setTimeout( () => {
				setShowSurveyModal( true );
				
				// Track that survey is being displayed (update user meta via AJAX)
				trackSurveyDisplay();
			}, 500 ); // 500ms delay to allow print dialogs to close
		}
	}, [ loaded, showPurchaseDialog, purchaseDialogWasShown ] );

	const onClose = () => props.exitPrintingFlow(props.orderId, props.siteId, false);

	const trackSurveyDisplay = async () => {
		try {
			await fetch( window.wcsMigrationSurvey.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'wcs_migration_survey_track_display',
					nonce: window.wcsMigrationSurvey.nonce,
				} ),
			} );
		} catch ( error ) {
			console.error( 'Failed to track survey display:', error );
		}
	};

	const handleSurveySubmit = async ( surveyData ) => {
		try {
			await fetch( window.wcsMigrationSurvey.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'wcs_migration_survey_submit',
					nonce: window.wcsMigrationSurvey.nonce,
					survey_data: JSON.stringify( surveyData ),
				} ),
			} );
		} catch ( error ) {
			console.error( 'Failed to submit survey:', error );
		}
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
				onSubmit={ handleSurveySubmit }
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
