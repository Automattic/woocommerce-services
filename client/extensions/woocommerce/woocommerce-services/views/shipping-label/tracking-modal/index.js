/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import ButtonModal from 'components/button-modal';
import { exitTrackingFlow } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	isCustomsFormRequired,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import ActivityLog from '../../../../app/order/order-activity-log/events';

const TrackingModal = props => {
	const { loaded, translate } = props;

	if ( ! loaded ) {
		return null;
	}

	const onClose = () => props.exitTrackingFlow( props.orderId, props.siteId, false );

	return (
		<ButtonModal
			additionalClassNames="woocommerce tracking-modal wcc-root"
			isVisible={ props.showTrackingDialog }
			onClose={ onClose }
			isDismissible={ true }
			title={ translate( 'Which package would you like to track?' ) }
		>
			<div className="tracking-modal__content">
				<div className="tracking-modal__body">
					<div className="tracking-modal__main-section">
                        { /* eslint-disable-next-line wpcalypso/jsx-classname-namespace */ }
                        <div className="shipment-tracking__dummy-class order-activity-log">
                            <ActivityLog orderId={ props.orderId } siteId={ props.siteId } isModal={ true } />
                        </div>
					</div>
				</div>
			</div>
		</ButtonModal>
	);
};

TrackingModal.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		loaded,
		form: loaded && shippingLabel.form,
		showTrackingDialog: shippingLabel.showTrackingDialog,
		isCustomsFormRequired: isCustomsFormRequired( state, orderId, siteId ),
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators( { exitTrackingFlow }, dispatch );
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( TrackingModal ) );
