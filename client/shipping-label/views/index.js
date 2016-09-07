import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';
import PurchaseLabelDialog from './purchase';
import RefundDialog from './refund';
import ReprintDialog from './reprint';
import TrackingLink from './tracking-link';
import * as ShippingLabelActions from 'shipping-label/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import getFormErrors from 'shipping-label/state/selectors/errors';
import sum from 'lodash/sum';
import map from 'lodash/map';
import find from 'lodash/find';
import get from 'lodash/get';

// TODO: Look into turning this into a memoized selector
const getRatesTotal = ( selectedRates, availableRates ) => {
	const ratesCost = map( selectedRates, ( rateId, boxId ) => {
		const packageRates = get( availableRates, [ boxId, 'rates' ], false );

		if ( packageRates ) {
			const foundRate = find( packageRates, [ 'service_id', rateId ] );

			return foundRate ? foundRate.rate : 0;
		}
		return 0;
	} );

	return sum( ratesCost ).toFixed( 2 );
};

const ShippingLabelRootView = ( props ) => {
	const renderPurchaseLabelFlow = () => {
		return (
			<div>
				<PurchaseLabelDialog
					{ ...props.shippingLabel }
					{ ...props } />
				<Button onClick={ props.labelActions.openPrintingFlow } >
					{ __( 'Create label' ) }
				</Button>
			</div>
		);
	};

	const renderLabelActions = ( label, index ) => {
		return (
			<div key={ index }>
				<dl>
					<dt>{ __( 'Tracking' ) }</dt>
					<dd><TrackingLink { ...label }/></dd>

					<dt>{ __( 'Ship via' ) }</dt>
					<dd>{ label.service_name }</dd>

					<dt>{ __( 'Purchase date' ) }</dt>
					<dd>{
						// Safari ignores the 'locale' and 'options' argument. It will display
						// the full date (with timezone and everything) in the browser's locale (instead of WP lang)
						new Date( label.created ).toLocaleDateString( document.documentElement.lang, {
							day: 'numeric',
							month: 'long',
							year: 'numeric',
							hour: 'numeric',
							minute: 'numeric',
						} )
					}</dd>
				</dl>
				<p>
					<ReprintDialog
						reprintDialog={ props.shippingLabel.reprintDialog }
						{ ...props }
						{ ...label } />
					<Button onClick={ () => props.labelActions.openReprintDialog( label.label_id ) } >
						{ __( 'Reprint label' ) }
					</Button>

					<RefundDialog
						refundDialog={ props.shippingLabel.refundDialog }
						{ ...props }
						{ ...label } />
					<Button onClick={ () => props.labelActions.openRefundDialog( label.label_id ) } >
						{ __( 'Refund label' ) }
					</Button>
				</p>
			</div>
		);
	};

	const renderLabelsActions = () => {
		return (
			<div>
				{ props.shippingLabel.labels.map( renderLabelActions ) }
			</div>
		);
	};

	return (
		<div className="wcc-metabox-button-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			{ props.shippingLabel.labels.length ? renderLabelsActions() : renderPurchaseLabelFlow() }
		</div>
	);
};

ShippingLabelRootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	customerRateChoices: PropTypes.object.isRequired,
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
