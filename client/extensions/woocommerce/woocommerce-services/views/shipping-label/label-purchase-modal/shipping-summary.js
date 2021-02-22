/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import {
	getShippingLabel,
	isLoaded,
	getFormErrors,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	toggleStep,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import { hasNonEmptyLeaves } from 'woocommerce/woocommerce-services/lib/utils/tree';

const ShippingSummary = props => {
	const { translate, normalizationStatus, form, orderId, siteId } = props;
	const { origin, rates } = form;
	const labelCount = Object.keys( rates.values ).length;

	return (
		<div className="label-purchase-modal__shipping-summary-section">
			<div className="label-purchase-modal__shipping-summary-info">
				{ normalizationStatus.isSuccess ? (
					<div className="label-purchase-modal__shipping-summary-street">
						<div>
							{ translate( 'Shipping from' ) }
						</div>
						<div>
							{ origin.values.address }
							{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
							<a href="#" onClick={ () => props.toggleStep( orderId, siteId, 'origin', true ) }>{ translate( 'Edit' ) }</a>
							{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
						</div>
					</div>
				) : null }
			</div>
			<div className="label-purchase-modal__shipping-summarry-labels">
				{ labelCount + ' ' + translate( 'shipping label ready', 'shipping labels ready', { count: labelCount } ) }
			</div>
		</div>
	);
}

ShippingSummary.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
};

const getNormalizationStatus = ( {
	normalizationInProgress,
	errors,
	isNormalized,
	values,
	normalized,
} ) => {
	if ( normalizationInProgress ) {
		return { isProgress: true };
	}
	if ( hasNonEmptyLeaves( errors ) || ( isNormalized && ! normalized ) || ! isNormalized ) {
		return { isError: true };
	}
	if ( isNormalized ) {
		return isEqual( values, normalized ) ? { isSuccess: true } : { isWarning: true };
	}
	return {};
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );

	const form = shippingLabel.form.origin;
	const errors = loaded && getFormErrors( state, orderId, siteId ).origin;

	return {
		orderId,
		siteId,
		errors,
		form: shippingLabel.form,
		expanded: form.expanded,
		normalizationStatus: getNormalizationStatus( { ...form, errors } ),
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			toggleStep,
		},
		dispatch
	);
};

export default localize(
	connect(
		mapStateToProps,
		mapDispatchToProps
	)( ShippingSummary )
);
