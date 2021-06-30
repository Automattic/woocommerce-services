/** @format */

/**
 * External dependencies
 */
import React  from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { some } from 'lodash';
import { Card } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import ExtendedHeader from 'woocommerce/components/extended-header';
import CarrierAccountListItem from './list-item';

const CarrierAccounts = ({ translate, accounts = [] }) => {
	if ( accounts.length === 0 ) {
		return null;
	}

	return (
		<div>
			<ExtendedHeader
				label={translate('Carrier account')}
				description={translate('Set up your own carrier account by adding your credentials here')}
			/>
			<Card size="small" className={ classNames( "card", "carrier-accounts__list") }>
				<div className="carrier-accounts__header">
					<div className="carrier-accounts__header-icon" />
					<div className="carrier-accounts__header-name">{ translate( 'Name' ) }</div>
					{ some( accounts, 'account' ) && (
						<div className="carrier-accounts__header-credentials">{ translate( 'Credentials' ) }</div>
					) }

					<div className="carrier-accounts__header-actions" />
				</div>
				{ accounts.map( ( account, index ) => <CarrierAccountListItem key={index} accountData={account} /> ) }
			</Card>
		</div>
	);
}

CarrierAccounts.propTypes = {
	accounts: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.string,
			carrier: PropTypes.string.isRequired,
			account: PropTypes.string,
			type: PropTypes.string,
		} )
	),
};

export default localize( CarrierAccounts );
