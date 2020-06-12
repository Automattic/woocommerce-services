/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { trim } from 'lodash';
import CarrierIcon from '../../components/carrier-icon';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import Button from 'components/button';

const CarrierAccountListItem = ( {
	isPlaceholder,
	data,
	dimensionUnit,
	prefixActions,
	children,
	translate,
} ) => {
	if ( isPlaceholder ) {
		return (
			<div className="carrier-accounts__list-item">
				<div className="carrier-accounts__list-item__placeholder-carrier-icon">
					<div className="carrier-accounts__list-item__carrier-icion-placeholder">
						<span />
					</div>
				</div>
				<div className="carrier-accounts__list-item__name">
					<div className="carrier-accounts__list-item__name-placeholder">
						<span />
					</div>
				</div>
				<div className="carrier-accounts__list-item__actions">{ children }</div>
			</div>
		);
	}

	const renderIcon = carrierId => {
		return <div className="carrier-accounts__list-item__carrier-icon">
			<CarrierIcon carrier={ carrierId } size={ 18 } />
		</div>;
	};

	const renderName = name => {
		const carrierName = name && '' !== trim( name ) ? name : translate( 'Untitled' );
		return <div className="carrier-accounts__list-item__name">
			<span>{ carrierName }</span>
		</div>;
	};

	const renderCredentials = credentials => {
		return <div className="carrier-accounts__list-item__credentials">
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
		return <div className="carrier-accounts__list-item__actions">
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
