import React from 'react';
import Dialog from 'components/dialog';
import ActionButtons from 'components/action-buttons';
import { translate as __ } from 'lib/mixins/i18n';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import PreviewStep from './steps/preview';
import { sprintf } from 'sprintf-js';
import { getRatesTotal } from 'shipping-label/state/selectors/rates';

const PrintLabelDialog = ( props ) => {
	const currencySymbol = props.storeOptions.currency_symbol;

	const getPurchaseButtonLabel = () => {
		let label = __( 'Buy & Print' );
		const nPackages = props.form.packages.values.length;
		if ( nPackages ) {
			label += ' ' + ( 1 === nPackages ? __( '1 Label' ) : sprintf( __( '%d Labels' ), nPackages ) );
		}
		if ( props.canPurchase ) {
			label += ' (' + currencySymbol + getRatesTotal( props.form.rates ) + ')';
		}
		return label;
	};

	return (
		<Dialog
			isVisible={ props.showPurchaseDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal" >
			<div className="wcc-shipping-label-dialog__content">
				<h3 className="form-section-heading">
					{ 1 === props.form.packages.values.length ? __( 'Create shipping label' ) : __( 'Create shipping labels' ) }
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
					<div className="wcc-shipping-label-dialog__sidebar">
						<PreviewStep
							{ ...props }
							{ ...props.form.preview }
							errors={ props.errors.preview }
							showPreview={ props.canPurchase } />
					</div>
				</div>
				<ActionButtons buttons={ [
					{
						isDisabled: ! props.canPurchase || props.form.isSubmitting,
						onClick: props.labelActions.purchaseLabel,
						isPrimary: true,
						label: getPurchaseButtonLabel(),
					},
					{
						onClick: props.labelActions.exitPrintingFlow,
						label: __( 'Cancel' ),
					},
				] } />
			</div>
		</Dialog>
	);
};

export default PrintLabelDialog;
