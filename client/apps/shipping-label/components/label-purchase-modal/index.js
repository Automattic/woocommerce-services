/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Modal from 'components/modal';
import ActionButtons from 'components/action-buttons';
import LoadingSpinner from 'components/loading-spinner';
import getPDFSupport from 'lib/utils/pdf-support';
import AddressStep from './address-step';
import PackagesStep from './packages-step';
import RatesStep from './rates-step';
import Sidebar from './sidebar';
import { getRatesTotal } from 'apps/shipping-label/state/selectors/rates';
import FormSectionHeading from 'components/forms/form-section-heading';
import { confirmPrintLabel, purchaseLabel, exitPrintingFlow } from '../../state/actions';
import getFormErrors from '../../state/selectors/errors';
import canPurchase from '../../state/selectors/can-purchase';

const PurchaseDialog = ( props ) => {
	const getPurchaseButtonLabel = () => {
		if ( props.form.needsPrintConfirmation ) {
			return __( 'Print' );
		}

		if ( props.form.isSubmitting ) {
			return (
				<div>
					<LoadingSpinner inline />
					<span className="label-purchase-modal__purchasing-label">{ __( 'Purchasing...' ) }</span>
				</div>
			);
		}

		const noNativePDFSupport = ( 'addon' === getPDFSupport() );

		if ( props.canPurchase ) {
			const currencySymbol = props.currency_symbol;
			const ratesTotal = getRatesTotal( props.form.rates );

			if ( noNativePDFSupport ) {
				return __( 'Buy (%(currencySymbol)s%(ratesTotal)s)', { args: { currencySymbol, ratesTotal } } );
			}

			return __( 'Buy & Print (%(currencySymbol)s%(ratesTotal)s)', { args: { currencySymbol, ratesTotal } } );
		}

		if ( noNativePDFSupport ) {
			return __( 'Buy' );
		}

		return __( 'Buy & Print' );
	};

	const getPurchaseButtonAction = () => {
		if ( props.form.needsPrintConfirmation ) {
			return () => props.confirmPrintLabel( props.form.printUrl );
		}
		return props.purchaseLabel;
	};

	const buttons = [
		{
			isDisabled: ! props.form.needsPrintConfirmation && ( ! props.canPurchase || props.form.isSubmitting ),
			onClick: getPurchaseButtonAction(),
			isPrimary: true,
			label: getPurchaseButtonLabel(),
		},
	];

	const closeModal = () => props.exitPrintingFlow( false );

	if ( ! props.form.needsPrintConfirmation ) {
		buttons.push( {
			onClick: closeModal,
			label: __( 'Cancel' ),
		} );
	}

	return (
		<Modal
			isVisible={ props.showPurchaseDialog }
			onClose={ closeModal } >
			<div className="label-purchase-modal__content">
				<FormSectionHeading>
					{ 1 === props.form.packages.selected.length ? __( 'Create shipping label' ) : __( 'Create shipping labels' ) }
				</FormSectionHeading>
				<div className="label-purchase-modal__body">
					<div className="label-purchase-modal__main-section">
						<AddressStep type="origin" title={ __( 'Origin address' ) } />
						<AddressStep type="destination" title={ __( 'Destination address' ) } />
						<PackagesStep />
						<RatesStep />
					</div>
					<Sidebar />
				</div>
				<ActionButtons buttons={ buttons } />
			</div>
		</Modal>
	);
};

const mapStateToProps = ( state ) => {
	const loaded = state.shippingLabel.loaded;
	const storeOptions = loaded ? state.shippingLabel.storeOptions : {};
	return {
		form: state.shippingLabel.form,
		showPurchaseDialog: state.shippingLabel.showPurchaseDialog,
		currency_symbol: storeOptions.currency_symbol,
		errors: loaded && getFormErrors( state, storeOptions ),
		canPurchase: loaded && canPurchase( state, storeOptions ),
	};
};

const mapDispatchToProps = ( dispatch ) => {
	return bindActionCreators( { confirmPrintLabel, purchaseLabel, exitPrintingFlow }, dispatch );
};

export default connect( mapStateToProps, mapDispatchToProps )( PurchaseDialog );
