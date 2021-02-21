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
import { exitPrintingFlow } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	isCustomsFormRequired,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const LabelPurchaseModal = props => {
	const { loaded, translate, showPurchaseDialog } = props;

	if ( ! loaded ) {
		return null;
	}


	const onClose = () => props.exitPrintingFlow( props.orderId, props.siteId, false );

	return (
		showPurchaseDialog ? (
			<Modal
				className="woocommerce label-purchase-modal wcc-root"
				shouldCloseOnClickOutside={ false }
				onRequestClose={ onClose }
				title={ translate( 'Create shipping label', 'Create shipping labels', { count: Object.keys( props.form.packages.selected ).length } ) }
			>
				<div className="label-purchase-modal__content">
					<div className="label-purchase-modal__body">
						<div className="label-purchase-modal__main-section">
							<AddressStep
								type="origin"
								title={ translate( 'Origin address' ) }
								siteId={ props.siteId }
								orderId={ props.orderId }
							/>
							<AddressStep
								type="destination"
								title={ translate( 'Destination address' ) }
								siteId={ props.siteId }
								orderId={ props.orderId }
							/>
							<PackagesStep siteId={ props.siteId } orderId={ props.orderId } />
							{ props.isCustomsFormRequired && (
								<CustomsStep siteId={ props.siteId } orderId={ props.orderId } />
							) }
							<RatesStep siteId={ props.siteId } orderId={ props.orderId } />
						</div>
						<Sidebar siteId={ props.siteId } orderId={ props.orderId } />
					</div>
				</div>
			</Modal>
		) : null
	);
};

LabelPurchaseModal.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
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
	mapStateToProps,
	mapDispatchToProps
)( localize( LabelPurchaseModal ) );
