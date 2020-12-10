/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import CarrierIcon from '../../components/carrier-icon';

const SubscriptionsUsageListItem = ( props ) => {
	const { translate, data } = props;
	 
	const getIcon = ( productName ) => {
	const subscriptionToIconRules = [
		[/^DHL/g, 'dhlexpress'],
		[/^UPS/g, 'ups'],
		[/^USPS/g, 'usps'],
	];

	const i = subscriptionToIconRules.findIndex( ( [rule] ) => rule.test( productName ));
	if (i > -1) return subscriptionToIconRules[i][1];
	
	return '';
	};

	const usage = `${ data.usage_count }/${ data.usage_limit }`;
	return (
		<div className= "subscriptions-usage__list-item" >
			<div className="subscriptions-usage__list-item-carrier-icon">
				<CarrierIcon carrier={ getIcon( data.product_name ) } size={ 18 } />
			</div>
			<div className="subscriptions-usage__list-item-name">
				<span>{ data.product_name }</span>
			</div>
			<div className="subscriptions-usage__list-item-usage">
				<span>{ usage }</span>
			</div>
			<div className="subscriptions-usage__list-item-actions">
				<a
					href={
						`https://woocommerce.com/my-account/my-subscriptions/`
					}
					// eslint-disable-next-line wpcalypso/jsx-classname-namespace
					className="button is-compact"
				>
					{ translate( 'Manage' ) }
				</a>
			</div>
		</div>
	);
};

SubscriptionsUsageListItem.propTypes = {
	data: PropTypes.shape( {
		product_name: PropTypes.string.isRequired,
		usage_limit: PropTypes.number.isRequired,
		usage_count: PropTypes.number,
	} ).isRequired,
};

export default localize( SubscriptionsUsageListItem );