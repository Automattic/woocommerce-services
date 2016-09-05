import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';
import PrintLabelDialog from './dialog';
import * as ShippingLabelActions from 'shipping-label/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import getFormErrors from 'shipping-label/state/selectors/errors';
import sum from 'lodash/sum';
import map from 'lodash/map';
import find from 'lodash/find';

// TODO: Look into turning this into a memoized selector
const getRatesTotal = ( selectedRates, availableRates ) => {
	const ratesCost = map( selectedRates, ( rateId, boxId ) => {
		if ( availableRates[ boxId ] ) {
			const foundRate = find( availableRates[ boxId ], [ 'service_id', rateId ] );

			return foundRate ? foundRate.rate : 0;
		}
		return 0;
	} );

	return sum( ratesCost ).toFixed( 2 );
};

const ShippingLabelRootView = ( props ) => {
	const renderPrintLabelFlow = () => {
		return (
			<Button onClick={ props.labelActions.openPrintingFlow } >
				{ __( 'Create label' ) }
			</Button>
		);
	};

	const renderSuccessNotice = () => {
		return <span>{ __( 'The shipping label has been successfully purchased.' ) }</span>;
	};

	return (
		<p className="wcc-metabox-button-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<PrintLabelDialog
				{ ...props.shippingLabel }
				{ ...props }
			/>
			{ props.shippingLabel.labels.purchased_time ? renderSuccessNotice() : renderPrintLabelFlow() }
		</p>
	);
};

ShippingLabelRootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state, { storeOptions } ) {
	return {
		shippingLabel: state.shippingLabel,
		errors: getFormErrors( state, storeOptions ),
		ratesTotal: getRatesTotal( state.shippingLabel.form.rates.values, state.shippingLabel.form.rates.available ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		labelActions: bindActionCreators( ShippingLabelActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ShippingLabelRootView );
