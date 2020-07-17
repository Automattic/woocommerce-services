/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { trim } from 'lodash';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CarrierIcon from '../../components/carrier-icon';
import Dialog from 'components/dialog';
import { getCarrierAccountsState } from 'woocommerce/woocommerce-services/state/carrier-accounts/selectors';
import {
	setVisibilityDisconnectCarrierDialog,
	disconnectCarrier,
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';

export const CarrierAccountListItem = ( props ) => {
	const { data, disconnected, isSaving, translate, siteId, showDisconnectDialog } = props;

	if ( disconnected ) {
		delete data.id;
		delete data.account;
	}

	const renderIcon = ( carrierId ) => {
		return (
			<div className="carrier-accounts__list-item-carrier-icon">
				<CarrierIcon carrier={ carrierId } size={ 18 } />
			</div>
		);
	};

	const renderName = ( name ) => {
		const carrierName = name && '' !== trim( name ) ? name : translate( 'Untitled' );
		return (
			<div className="carrier-accounts__list-item-name">
				<span>{ carrierName }</span>
			</div>
		);
	};

	const renderCredentials = ( credentials ) => {
		return (
			<div className="carrier-accounts__list-item-credentials">
				<span>{ credentials }</span>
			</div>
		);
	};

	const showDisconnectDialogHandler = () => {
		props.setVisibilityDisconnectCarrierDialog( siteId, data.carrier, true );
	};

	const hideDisconnectDialogHandler = () => {
		props.setVisibilityDisconnectCarrierDialog( siteId, data.carrier, false );
	};

	const connectCarrierHandler = () => {
		const url = new URL( window.location.href );
		url.searchParams.set( 'carrier', data.carrier );
		window.location.href = url.href;
	};

	const renderActions = ( credentials ) => {
		const connectButton = () => {
			return (
				<Button compact onClick={ connectCarrierHandler }>
					{ translate( 'Connect' ) }
				</Button>
			);
		};
		const disconnectButton = () => {
			return (
				<Button onClick={ showDisconnectDialogHandler } compact scary borderless>
					{ translate( 'Disconnect' ) }
				</Button>
			);
		};
		return (
			<div className="carrier-accounts__list-item-actions">
				{ credentials ? disconnectButton() : connectButton() }
			</div>
		);
	};

	const cancelDialogButton = () => {
		return [
			<Button
				compact
				primary
				disabled={ isSaving }
				onClick={ () => props.setVisibilityDisconnectCarrierDialog( siteId, data.carrier, false ) }
			>
				{ translate( 'Cancel' ) }
			</Button>,
			<Button
				compact
				primary
				scary
				disabled={ isSaving }
				busy={ isSaving }
				onClick={ () => props.disconnectCarrier( siteId, data.carrier, data.id ) }
			>
				{ translate( 'Disconnect' ) }
			</Button>,
		];
	};

	return (
		<div className="carrier-accounts__list-item">
			{ renderIcon( data.carrier ) }
			{ renderName( data.carrier ) }
			{ renderCredentials( data.account ) }
			{ renderActions( data.account ) }
			<Dialog
				isVisible={ showDisconnectDialog }
				additionalClassNames="carrier-accounts__settings-cancel-dialog"
				onClose={ hideDisconnectDialogHandler }
				buttons={ cancelDialogButton() }
			>
				<div className="carrier-accounts__settings-cancel-dialog-header">
					<h2 className="carrier-accounts__settings-cancel-dialog-title">
						{ translate( 'Disconnect your UPS account' ) }
					</h2>
					<button
						className="carrier-accounts__settings-cancel-dialog-close-button"
						onClick={ hideDisconnectDialogHandler }
					>
						<Gridicon icon="cross" />
					</button>
				</div>
				<p className="carrier-accounts__settings-cancel-dialog-description">
					{ translate(
						'This will remove the connection with UPS. All of your UPS account information will be deleted and you won’t see UPS rates when purchasing shipping labels.'
					) }
				</p>
			</Dialog>
		</div>
	);
};

CarrierAccountListItem.propTypes = {
	siteId: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		id: PropTypes.string,
		carrier: PropTypes.string.isRequired,
		account: PropTypes.string,
	} ).isRequired,
};

const mapStateToProps = ( state, { siteId, data } ) => {
	const carrier = data.carrier;
	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const { disconnected, isSaving, showDisconnectDialog } = carrierAccountState;

	const ret = {
		disconnected,
		isSaving,
		showDisconnectDialog,
	};

	return ret;
};

const mapDispatchToProps = ( dispatch ) => {
	return bindActionCreators(
		{
			setVisibilityDisconnectCarrierDialog,
			disconnectCarrier,
		},
		dispatch
	);
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( CarrierAccountListItem ) );
