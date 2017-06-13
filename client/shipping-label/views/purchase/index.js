/**
 * External dependencies
 */
import React from 'react';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Modal from 'components/modal';
import ActionButtons from 'components/action-buttons';
import Spinner from 'components/spinner';
import getPDFSupport from 'lib/utils/pdf-support';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import Sidebar from './sidebar';
import { getRatesTotal } from 'shipping-label/state/selectors/rates';

const PrintLabelDialog = ( props ) => {
	const getPurchaseButtonLabel = () => {
		if ( props.form.needsPrintConfirmation ) {
			return __( 'Print' );
		}

		if ( props.form.isSubmitting ) {
			return (
				<div>
					<Spinner size={ 24 } />
					<span className="wcc-shipping-label-dialog__purchasing-label">{ __( 'Purchasing...' ) }</span>
				</div>
			);
		}

		const noNativePDFSupport = ( 'addon' === getPDFSupport() );

		if ( props.canPurchase ) {
			const currencySymbol = props.storeOptions.currency_symbol;
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
			return () => props.labelActions.confirmPrintLabel( props.form.printUrl );
		}
		return props.labelActions.purchaseLabel;
	};

	const buttons = [
		{
			isDisabled: ! props.form.needsPrintConfirmation && ( ! props.canPurchase || props.form.isSubmitting ),
			onClick: getPurchaseButtonAction(),
			isPrimary: true,
			label: getPurchaseButtonLabel(),
		},
	];

	const exitPrintingFlow = () => props.labelActions.exitPrintingFlow( false );

	if ( ! props.form.needsPrintConfirmation ) {
		buttons.push( {
			onClick: exitPrintingFlow,
			label: __( 'Cancel' ),
		} );
	}

	return (
		<Modal
			isVisible={ props.showPurchaseDialog }
			onClose={ exitPrintingFlow } >
			<div className="wcc-shipping-label-dialog__content">
				<h3 className="form-section-heading">
					{ 1 === props.form.packages.selected.length ? __( 'Create shipping label' ) : __( 'Create shipping labels' ) }
				</h3>
				<div className="wcc-shipping-label-dialog__body">
					<div className="wcc-shipping-label-dialog__main-section">
						<AddressStep.Origin
							{ ...props }
							{ ...props.form.origin }
							errors={ props.errors.origin } />
						<AddressStep.Destination
							{ ...props }
							{ ...props.form.destination }
							errors={ props.errors.destination } />
						<PackagesStep
							{ ...props }
							{ ...props.form.packages }
							errors={ props.errors.packages } />
						<RatesStep
							{ ...props }
							{ ...props.form.rates }
							errors={ props.errors.rates } />
					</div>
					<Sidebar
						{ ...props }
						errors={ props.errors.rates } />
				</div>
				<ActionButtons buttons={ buttons } />
			</div>
		</Modal>
	);
};

export default PrintLabelDialog;
