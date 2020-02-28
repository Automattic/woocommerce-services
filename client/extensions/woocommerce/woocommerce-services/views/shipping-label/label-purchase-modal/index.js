/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Dialog from 'components/dialog';
import AddressStep from './address-step';
import PackagesStep from './packages-step';
import CustomsStep from './customs-step';
import RatesStep from './rates-step';
import Sidebar from './sidebar';
import FormSectionHeading from 'components/forms/form-section-heading';
import { exitPrintingFlow } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import Button from 'components/button';
import {
	getShippingLabel,
	isLoaded,
	isCustomsFormRequired,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const LabelPurchaseModal = props => {
	const { loaded, translate } = props;

	if ( ! loaded ) {
		return null;
	}

	const onClose = () => props.exitPrintingFlow( props.orderId, props.siteId, false );
	let dialogClasses = "woocommerce label-purchase-modal label-purchase-modal__sidebar_background wcc-root";
	if (props.hasJetpackAccount) {
		dialogClasses = dialogClasses + " label-purchase-modal__sidebar_background";
	}

	const LabelPurchaseBody = (props) => {
		return (
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
		);
	}

	const JetpackConnectBody = (props) => {
		return (
			<>
				<div className="label-purchase-modal__header">
					<h3 className="form-section-heading">Create a Jetpack account</h3>
				</div>
				<iframe
					src={wcConnectData.jetpack_auth_url}
					className="label-purchase-modal__jetpack-connect"
				></iframe>
			</>
		);
	}

	return (
		<Dialog
			additionalClassNames={ dialogClasses }
			isVisible={ props.showPurchaseDialog }
			onClose={ onClose }
		>
			<div className="label-purchase-modal__content">
				<div className="label-purchase-modal__header">
					<FormSectionHeading>
						{ translate( 'Create shipping label', 'Create shipping labels', { count: Object.keys( props.form.packages.selected ).length } ) }
					</FormSectionHeading>
					<Button className="label-purchase-modal__close-button" onClick={ onClose }>
						<Gridicon icon="cross" />
					</Button>
				</div>

				{ (props.hasJetpackAccount) ? <LabelPurchaseBody {...props} /> : <JetpackConnectBody {...props} /> }
			</div>
		</Dialog>
	);
};

LabelPurchaseModal.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	hasJetpackAccount: PropTypes.bool.isRequired
};

LabelPurchaseBody.propTypes = {
	...LabelPurchaseModal.propTypes
};

JetpackConnectBody.propTypes = {
	...LabelPurchaseModal.propTypes
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
