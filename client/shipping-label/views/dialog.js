import React from 'react';
import Dialog from 'components/dialog';
import ActionButtons from 'components/action-buttons';
import { translate as __ } from 'lib/mixins/i18n';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import PreviewStep from './steps/preview';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import { sprintf } from 'sprintf-js';
import isEmpty from 'lodash/isEmpty';

const PrintLabelDialog = ( props ) => {
	const currencySymbol = props.storeOptions.currency_symbol;

	const canPurchase = ! props.form.isSubmitting &&
		! hasNonEmptyLeaves( props.errors ) &&
		! props.form.origin.normalizationInProgress &&
		! props.form.destination.normalizationInProgress &&
		! props.form.rates.retrievalInProgress &&
		! isEmpty( props.form.rates.available );

	const getPurchaseButtonLabel = () => {
		let label = __( 'Buy & Print' );
		const nPackages = props.form.packages.values.length;
		if ( nPackages ) {
			label += ' ' + ( 1 === nPackages ? __( '1 Label' ) : sprintf( __( '%d Labels' ), nPackages ) );
		}
		if ( canPurchase ) {
			label += ' (' + currencySymbol + props.ratesTotal + ')';
		}
		return label;
	};

	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal wcc-shipping-label-dialog" >
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
							errors={ props.errors.preview } />
					</div>
				</div>
				<ActionButtons buttons={ [
					{
						isDisabled: ! canPurchase,
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
