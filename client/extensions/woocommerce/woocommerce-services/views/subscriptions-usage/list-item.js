/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { Button, Tooltip } from '@wordpress/components';
import Gridicon from 'gridicons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import CarrierIcon from '../../components/carrier-icon';
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice as errorNoticeAction, successNotice as successNoticeAction } from 'state/notices/actions';
import { getSelectedSiteId } from 'state/ui/selectors';

const SubscriptionsUsageListItem = ( props ) => {
	const { translate, data, errorNotice, successNotice, siteId } = props;
	const [isActive, setisActive] = React.useState( data.is_active );
	const [isSaving, setIsSaving] = React.useState(false);

	const getIcon = ( productName ) => {
		const subscriptionToIconRules = [
			[/^DHL/g, 'dhlexpress'],
			[/^UPS/g, 'ups'],
			[/^USPS/g, 'usps'],
		];

		const i = subscriptionToIconRules.findIndex( ( [rule] ) => rule.test( productName ) );
		if ( i > -1 ) return subscriptionToIconRules[i][1];

		return '';
	};

	const handleActivate = () =>{
		const submitActivation = async () => {
			setIsSaving(true);

			try {
				await api.post( siteId, api.url.subscriptionActivate( data.product_key ) );
				setisActive( true );
				successNotice( translate( 'Your subscription was succesfully activated.' ) );
			} catch (err) {
				errorNotice( translate( 'There was an error trying to activate your subscription.' ) );
			}

			setIsSaving(false);
		}
		submitActivation();
	}

	const usage = [];
	let isUsageOverLimit = false;

	data.usage_data.forEach( ( usageEvent, index ) => {
		usage.push( <div key={ index }>{ usageEvent.label }: { usageEvent.count }/{ usageEvent.limit }</div> );
		isUsageOverLimit = isUsageOverLimit || usageEvent.count > usageEvent.limit;
	} );

	const buttonClasses = classNames( 'button','is-compact');

	return (
		<div className= "subscriptions-usage__list-item" >
			<div className="subscriptions-usage__list-item-carrier-icon">
				<CarrierIcon carrier={ getIcon( data.product_name ) } size={ 18 } />
			</div>
			<div className="subscriptions-usage__list-item-name">
				<span>{ data.product_name }</span>
			</div>
			<div className={ classNames( 'subscriptions-usage__list-item-usage', { 'is-over-limit': isUsageOverLimit } ) }>
				<span className="subscriptions-usage__list-item-usage-numbers">{ usage }</span>
				<Tooltip
					text={ translate(
						'Your store is over the limit for rate calls this month and your account will be charged for overages. ' +
						'To prevent future overage charges you can upgrade your plan.'
					) }
				>
					<span className="subscriptions-usage__list-item-usage-icon">
						<Gridicon icon="info-outline" size={ 18 } />
					</span>
				</Tooltip>
			</div>
			<div className="subscriptions-usage__list-item-actions">
				{ isActive ? (
					<a
						href={
						`https://woocommerce.com/my-account/my-subscriptions/`
						}
						className= { buttonClasses }
					>
						{ translate( 'Manage' ) }
					</a>
				) : (
					<Button
						onClick={ handleActivate }
						disabled={ isSaving }
						isBusy={ isSaving }
						className= { buttonClasses }
					>
						{ translate( 'Activate' ) }
				   </Button>
				) }
			</div>
		</div>
	);
};

SubscriptionsUsageListItem.propTypes = {
	errorNotice: PropTypes.func.isRequired,
	successNotice: PropTypes.func.isRequired,
	siteId: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		product_name: PropTypes.string.isRequired,
		is_active: PropTypes.bool.isRequired,
		usage_data: PropTypes.arrayOf( PropTypes.shape( {
			limit: PropTypes.number,
			count: PropTypes.number,
			label: PropTypes.string,
		} ) )
	} ).isRequired,
};

const mapStateToProps = (state) => ({
	siteId: getSelectedSiteId( state ),
});

const mapDispatchToProps = {
	errorNotice:errorNoticeAction,
	successNotice:successNoticeAction,
}

export default compose( connect( mapStateToProps, mapDispatchToProps ), localize )( SubscriptionsUsageListItem );
