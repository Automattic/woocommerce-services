/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import LabelPurchaseDialog from 'woocommerce/woocommerce-services/views/shipping-label/label-purchase-modal';
import QueryLabels from 'woocommerce/woocommerce-services/components/query-labels';
import { openPrintingFlow } from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import { getSelectedSiteId } from 'state/ui/selectors';

const ShippingLabelViewWrapper = ( props ) => {
	const {
		translate,
		siteId,
		orderId,
	} = props;

	const onLabelPrint = () => props.openPrintingFlow( orderId, siteId );

	return (
		<div>
			<GlobalNotices notices={ notices.list } />
			<QueryLabels orderId={ orderId } siteId={ siteId } />
			<LabelPurchaseDialog orderId={ orderId } siteId={ siteId } />
			<Button
				primary
				onClick={ onLabelPrint }
			>
				{ translate( 'Create new label' ) }
			</Button>
		</div>
	);
};

ShippingLabelViewWrapper.propTypes = {
	orderId: PropTypes.number.isRequired,
};

export default connect(
	( state ) => {
		const siteId = getSelectedSiteId( state );

		return {
			siteId,
		};
	},
	( dispatch ) => ( {
		...bindActionCreators( { openPrintingFlow }, dispatch ),
	} ),
)( localize( ShippingLabelViewWrapper ) );
