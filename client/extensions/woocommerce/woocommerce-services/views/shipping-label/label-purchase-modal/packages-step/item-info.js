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
import { getSite } from 'state/sites/selectors';
import { openItemMove } from 'woocommerce/woocommerce-services/state/shipping-label/actions';

const ItemInfo = props => {
	const { orderId, siteId, item, itemIndex, translate } = props;
	const onMoveClick = () => props.openItemMove( orderId, siteId, itemIndex );

	const renderMoveToPackage = () => {
		return (
			/* eslint-disable jsx-a11y/anchor-is-valid */
			<a href="#" onClick={ onMoveClick }>
				{ translate( 'Move' ) }
			</a>
			/* eslint-enable jsx-a11y/anchor-is-valid */
		);
	};

	const productLink = item.url ? (
		<a href={ item.url } target="_blank" rel="noopener noreferrer">
			{ item.name }
		</a>
	) : (
		item.name
	);

	return (
		<div key={ itemIndex } className="packages-step__item">
			<div className="packages-step__item-name">
				<span>{ productLink }</span>
				{ 'attributes' in item ? <p>{ item.attributes }</p> : '' }
			</div>
			<div className="packages-step__item-weight">
				{ 'weight' in item ? <p>{ item.weight }</p> : '' }
			</div>
			<div className="packages-step__item-qty">
				{ 'quantity' in item ? <p>{ item.quantity }</p> : '' }
			</div>
			<div className="packages-step__item-move">
				{ renderMoveToPackage() }
			</div>
		</div>
	);
};

ItemInfo.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	item: PropTypes.object.isRequired,
	itemIndex: PropTypes.number.isRequired,
	openItemMove: PropTypes.func.isRequired,
};

export default connect(
	( state, { siteId } ) => ( {
		site: getSite( state, siteId ),
	} ),
	dispatch =>
		bindActionCreators(
			{
				openItemMove,
			},
			dispatch
		)
)( localize( ItemInfo ) );
