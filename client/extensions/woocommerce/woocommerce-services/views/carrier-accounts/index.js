/** @format */

/**
 * External dependencies
 */

import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { isEmpty, some } from 'lodash';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import ExtendedHeader from 'woocommerce/components/extended-header';
import CarrierAccountListItem from './list-item';

export class CarrierAccounts extends Component {
	renderListHeader = ( carriers ) => {
		const { translate } = this.props;

		return (
			<div className="carrier-accounts__header">
				<div className="carrier-accounts__header-icon" />
				<div className="carrier-accounts__header-name">{ translate( 'Name' ) }</div>
				{ some( carriers, 'account' ) && (
					<div className="carrier-accounts__header-credentials">{ translate( 'Credentials' ) }</div>
				) }

				<div className="carrier-accounts__header-actions" />
			</div>
		);
	};

	renderListItem = ( carrier, index ) => {
		const { siteId, isFetching } = this.props;

		return <CarrierAccountListItem key={ index } siteId={ siteId } isPlaceholder={ isFetching } data={ carrier } />;
	};

	render() {
		const { translate } = this.props;

		const carriers = this.props.carriers || [];

		if ( isEmpty( carriers ) ) {
			return <div></div>;
		}
		return (
			<div>
				<ExtendedHeader
					label={ translate( 'Carrier account' ) }
					description={ translate( 'Set up your own carrier account by adding your credentials here' ) }
				></ExtendedHeader>
				<Card className="carrier-accounts__list">
					{ this.renderListHeader( carriers ) }
					{ carriers.map( this.renderListItem ) }
				</Card>
			</div>
		);
	}
}

CarrierAccounts.propTypes = {
	siteId: PropTypes.number.isRequired,
	carriers: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.string,
			carrier: PropTypes.string.isRequired,
			account: PropTypes.string,
		} )
	),
};

export default localize( CarrierAccounts );
