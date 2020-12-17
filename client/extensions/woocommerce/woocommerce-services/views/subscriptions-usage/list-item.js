/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { compose } from 'redux';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import CarrierIcon from '../../components/carrier-icon';
import Button from 'components/button';
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice as errorNoticeAction, successNotice as successNoticeAction } from 'state/notices/actions';
import { getSelectedSiteId } from 'state/ui/selectors';

const SubscriptionsUsageListItem = ( props ) => {
	const { translate, data, errorNotice, successNotice, siteId } = props;
	const [canBeActivated, setCanBeActivated] = React.useState( data.sites_active < data.sites_max );
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
				setCanBeActivated( false );
				successNotice( translate( 'Your subscription was succesfully activated.' ) );
			} catch (err) {
				errorNotice( translate( 'There was an error trying to activate your subscription.' ) );
			}

			setIsSaving(false);
		}
		submitActivation();
	}

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
				{ canBeActivated ? (
					<Button 
						onClick={ handleActivate } 
						disabled={ isSaving }
						busy={ isSaving }
						compact
					>
						{ translate( 'Activate' ) }
				   </Button>
				) : (
					<a
					href={
						`https://woocommerce.com/my-account/my-subscriptions/`
					}
					// eslint-disable-next-line wpcalypso/jsx-classname-namespace
					className="button is-compact"
					>
						{ translate( 'Manage' ) }
					</a>
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
		usage_limit: PropTypes.number.isRequired,
		usage_count: PropTypes.number,
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