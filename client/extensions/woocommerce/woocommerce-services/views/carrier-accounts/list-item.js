/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { trim } from 'lodash';
import CarrierIcon from '../../components/carrier-icon';

/**
 * Internal dependencies
 */
import Button from 'components/button';

const CarrierAccountListItem = ( {
	isPlaceholder,
	data,
	children,
	translate,
} ) => {
	if ( isPlaceholder ) {
		return (
			<div className="carrier-accounts__list-item">
				<div className="carrier-accounts__list-item-placeholder-carrier-icon">
					<div className="carrier-accounts__list-item-carrier-icion-placeholder">
						<span />
					</div>
				</div>
				<div className="carrier-accounts__list-item-name">
					<div className="carrier-accounts__list-item-name-placeholder">
						<span />
					</div>
				</div>
				<div className="carrier-accounts__list-item-actions">{ children }</div>
			</div>
		);
	}

	const renderIcon = carrierId => {
		return <div className="carrier-accounts__list-item-carrier-icon">
			<CarrierIcon carrier={ carrierId } size={ 18 } />
		</div>;
	};

	const renderName = name => {
		const carrierName = name && '' !== trim( name ) ? name : translate( 'Untitled' );
		return <div className="carrier-accounts__list-item-name">
			<span>{ carrierName }</span>
		</div>;
	};

	const renderCredentials = credentials => {
		return <div className="carrier-accounts__list-item-credentials">
			<span>{ credentials }</span>
		</div>;
	}

	const renderActions = () => {
		const { credentials,  onConnect, onDisconnect } = data;
		const connectButton = () => {
			return <Button compact onClick={ () => { onConnect( data ) } }>
				{ translate( 'Connect' ) }
			</Button>
		};
		const disconnectButton = () => {
			return <Button onClick={ () => { onDisconnect( data ) } } compact scary borderless >
				{ translate( 'Disconnect' ) }
			</Button>
		};
		return <div className="carrier-accounts__list-item-actions">
			{ credentials ? disconnectButton() : connectButton() }
		</div>;
	}

	return (
		<div className="carrier-accounts__list-item">
			{ renderIcon( data.carrierId ) }
			{ renderName( data.name ) }
			{ renderCredentials( data.credentials ) }
			{ renderActions( data ) }
		</div>
	);
};

CarrierAccountListItem.propTypes = {
	siteId: PropTypes.number.isRequired,
	isPlaceholder: PropTypes.bool,
	data: PropTypes.shape( {
		name: PropTypes.string,
		carrierId: PropTypes.string,
		credentials: PropTypes.string,
	} ).isRequired,
};

export default localize( CarrierAccountListItem );
