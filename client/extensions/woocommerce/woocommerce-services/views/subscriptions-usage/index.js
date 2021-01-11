/**
 * External dependencies
 */
import React  from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import ExtendedHeader from 'woocommerce/components/extended-header';
import SubscriptionsUsageListItem from './list-item';

const SubscriptionsUsage = ({ translate, subscriptions = [] }) => {
	if ( subscriptions.length === 0 ) {
		return null;
	}

	return (
		<div>
			<ExtendedHeader
				label={translate('Shipping method')}
				description={translate('View and manage your subscription usage')}
			/>
			<Card className="subscriptions-usage__list">
				<div className="subscriptions-usage__header">
					<div className="subscriptions-usage__header-icon" />
					<div className="subscriptions-usage__header-name">{ translate( 'Name' ) }</div>
					<div className="subscriptions-usage__header-usage">{ translate( 'Usage' ) }</div>
					<div className="subscriptions-usage__header-actions" />
				</div>
				{ subscriptions.map( ( subscription, index ) => <SubscriptionsUsageListItem key={ index } data={ subscription } /> ) }
			</Card>
		</div>
	);
}

SubscriptionsUsage.propTypes = {
	subscriptions: PropTypes.arrayOf(
		PropTypes.shape( {
			product_name: PropTypes.string.isRequired,
			usage_limit: PropTypes.number,
			usage_count: PropTypes.number,
		} )
	),
};

export default localize( SubscriptionsUsage );
