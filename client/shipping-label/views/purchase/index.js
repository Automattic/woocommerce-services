import React from 'react';
import Modal from 'components/modal';
import ActionButtons from 'components/action-buttons';
import Spinner from 'components/spinner';
import { translate as __ } from 'lib/mixins/i18n';
import getPDFSupport from 'lib/utils/pdf-support';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import Sidebar from './sidebar';
import { sprintf } from 'sprintf-js';
import { getRatesTotal } from 'shipping-label/state/selectors/rates';

const PrintLabelDialog = ( props ) => {
	const currencySymbol = props.storeOptions.currency_symbol;

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
		let label = 'addon' === getPDFSupport() ? __( 'Buy' ) : __( 'Buy & Print' );
		const nPackages = props.form.packages.selected.length;
		if ( nPackages ) {
			label += ' ' + ( 1 === nPackages ? __( '1 Label' ) : sprintf( __( '%d Labels' ), nPackages ) );
		}
		if ( props.canPurchase ) {
			label += ' (' + currencySymbol + getRatesTotal( props.form.rates ) + ')';
		}
		return label;
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

	if ( ! props.form.needsPrintConfirmation ) {
		buttons.push( {
			onClick: () => props.labelActions.exitPrintingFlow( false ),
			label: __( 'Cancel' ),
		} );
	}

	return (
		<Modal
			isVisible={ props.showPurchaseDialog }
			onClose={ () => props.labelActions.exitPrintingFlow( false ) } >
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
