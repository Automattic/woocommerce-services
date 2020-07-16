/** @format */

/**
 * External dependencies
 */

import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { some } from 'lodash';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import ExtendedHeader from 'woocommerce/components/extended-header';
import CarrierAccountListItem from './list-item';
import * as CarrierAccountsActions from '../../state/carrier-accounts/actions';
import { getSelectedSiteId } from 'state/ui/selectors';

class CarrierAccounts extends Component {
	renderListHeader = carriers => {
		const { translate } = this.props;

		return (
			<div className="carrier-accounts__header">
				<div className="carrier-accounts__header-icon" />
				<div className="carrier-accounts__header-name">{ translate( 'Name' ) }</div>
				{ some(carriers, 'credentials' ) && <div className="carrier-accounts__header-credentials">{ translate( 'Credentials' ) }</div>}

				<div className="carrier-accounts__header-actions" />
			</div>
		);
	};

	renderListItem = ( carrier, index ) => {
		const { siteId, isFetching } = this.props;

		return (
			<CarrierAccountListItem
				key={ index }
				siteId={ siteId }
				isPlaceholder={ isFetching }
				data={ carrier }
			/>
		);
	};

	render() {
		const { translate } = this.props;

		const onConnect = ( carrier ) => {
			const url = new URL( window.location.href );
			url.searchParams.set( 'carrier', carrier.carrierId );
			window.location.href = url.href;
		}

		const onDisconnect = () => {
		}

		const carriers = [
			{ carrierId: 'UPS', name: 'UPS', onConnect },
			{ carrierId: 'UPS', name: 'UPS', credentials: '989999847463', onDisconnect },
		]

		return (
			<div>
				<ExtendedHeader
					label={ translate( 'Carrier account' ) }
					description={ translate(
						'Set up your own carrier account by adding your credentials here.'
					) }
				>
				</ExtendedHeader>
				<Card className="carrier-accounts__list">
					{ this.renderListHeader( carriers ) }
					{ carriers.map( this.renderListItem ) }
				</Card>
			</div>
		);
	}
}

CarrierAccounts.propTypes = {
	isShowingSettings: PropTypes.shape( {
		carrierId: PropTypes.string.isRequired,
		name: PropTypes.string.isRequired,
		credentials: PropTypes.string,
		onConnect: PropTypes.func,
		onDisconnect: PropTypes.func,
	} )
};

export default connect(
	state => {
		const siteId = getSelectedSiteId( state );

		return {
			siteId,
		};
	},
	dispatch => ( {
		...bindActionCreators( CarrierAccountsActions, dispatch ),
	} )
)( localize( CarrierAccounts ) );
