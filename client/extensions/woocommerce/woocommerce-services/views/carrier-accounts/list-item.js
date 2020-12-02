/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';
import { compose } from 'redux';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CarrierIcon from '../../components/carrier-icon';
import Dialog from 'components/dialog';
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice as errorNoticeAction, successNotice as successNoticeAction } from 'state/notices/actions'
import { getSelectedSiteId } from 'state/ui/selectors';

const CarrierAccountListItem = ( props ) => {
	const { data, translate, errorNotice, successNotice, siteId } = props;

	const [isDisconnectDialogVisible, setIsDisconnectDialogVisible] = React.useState(false);
	const [isSaving, setIsSaving] = React.useState(false);
	const [carrierId, setCarrierId] = React.useState(data.id);

	const handleShowDisconnectDialogConfirmation = React.useCallback(() => {
		setIsDisconnectDialogVisible(true);
	}, [setIsDisconnectDialogVisible]);

	const handleDisconnectDialogCancel = React.useCallback(() => {
		setIsDisconnectDialogVisible(false);
	}, [setIsDisconnectDialogVisible]);

	const handleDisconnectConfirmation = React.useCallback(() => {
		const submitDeletion = async () => {
			setIsSaving(true);

			try {
				await api.del( siteId, api.url.shippingCarrierDelete( carrierId ) )
				setIsDisconnectDialogVisible(false);
				successNotice( translate( 'Your carrier account was disconnected succesfully.' ) );
				setCarrierId(null);
			} catch (err) {
				errorNotice( translate( 'There was an error trying to disconnect your carrier account' ) );
			}

			setIsSaving(false);
		};

		submitDeletion();
	}, [setIsDisconnectDialogVisible, errorNotice, successNotice, setIsSaving, siteId, setCarrierId, carrierId]);

	const disconnectDialogButtons = React.useMemo(() => {
		return [
			<Button
				compact
				primary
				disabled={ isSaving }
				onClick={ handleDisconnectDialogCancel }
			>
				{ translate( 'Cancel' ) }
			</Button>,
			<Button
				compact
				primary
				scary
				disabled={ isSaving }
				busy={ isSaving }
				onClick={ handleDisconnectConfirmation }
			>
				{ translate( 'Disconnect' ) }
			</Button>,
		];
	}, [handleDisconnectDialogCancel, handleDisconnectConfirmation, isSaving]);

	const carrierTypeIconMap = {
		DhlExpressAccount: 'dhlexpress',
		UpsAccount: 'ups',
	}

	return (
		<div className="carrier-accounts__list-item">
			<div className="carrier-accounts__list-item-carrier-icon">
				<CarrierIcon carrier={ carrierTypeIconMap[data.type] } size={ 18 } />
			</div>
			<div className="carrier-accounts__list-item-name">
				<span>{ data.carrier }</span>
			</div>
			<div className="carrier-accounts__list-item-credentials">
				<span>{ carrierId ? data.account : null }</span>
			</div>
			<div className="carrier-accounts__list-item-actions">
				{ carrierId ? (
					<Button onClick={ handleShowDisconnectDialogConfirmation } compact scary borderless>
						{ translate( 'Disconnect' ) }
					</Button>
				) : (
					<a
						href={
							`/wp-admin/admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings&carrier=${data.type}`
						}
						// eslint-disable-next-line wpcalypso/jsx-classname-namespace
						className="button is-compact"
					>
						{ translate( 'Connect' ) }
					</a>
				) }
			</div>
			<Dialog
				isVisible={ isDisconnectDialogVisible }
				additionalClassNames="carrier-accounts__settings-cancel-dialog"
				onClose={ handleDisconnectDialogCancel }
				buttons={ disconnectDialogButtons }
			>
				<div className="carrier-accounts__settings-cancel-dialog-header">
					<h2 className="carrier-accounts__settings-cancel-dialog-title">
						{ translate( 'Disconnect your %(carrier_name)s account', {
							args: { carrier_name: data.carrier },
						} ) }
					</h2>
					<button
						className="carrier-accounts__settings-cancel-dialog-close-button"
						onClick={ handleDisconnectDialogCancel }
					>
						<Gridicon icon="cross" />
					</button>
				</div>
				<p className="carrier-accounts__settings-cancel-dialog-description">
					{ translate( 'This will remove the connection with %(carrier_name)s. All of your %(carrier_name)s account information will be deleted and you won’t see %(carrier_name)s rates.', {
						args: { carrier_name: data.carrier },
					} ) }
				</p>
			</Dialog>
		</div>
	);
};

CarrierAccountListItem.propTypes = {
	errorNotice: PropTypes.func.isRequired,
	successNotice: PropTypes.func.isRequired,
	siteId: PropTypes.number.isRequired,
	data: PropTypes.shape( {
		id: PropTypes.string,
		carrier: PropTypes.string.isRequired,
		account: PropTypes.string,
		type: PropTypes.string,
	} ).isRequired,
};

const mapStateToProps = (state) => ({
	siteId: getSelectedSiteId( state ),
});

const mapDispatchToProps = {
	errorNotice:errorNoticeAction,
	successNotice:successNoticeAction,
}

export default compose(connect(mapStateToProps, mapDispatchToProps),localize)( CarrierAccountListItem ) ;
