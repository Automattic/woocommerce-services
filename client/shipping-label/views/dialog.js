import React from 'react';
import Dialog from 'components/dialog';
import ActionButtons from 'components/action-buttons';
import { translate as __ } from 'lib/mixins/i18n';
import sum from 'lodash/sum';
import isArray from 'lodash/isArray';
import isPlainObject from 'lodash/isPlainObject';
import some from 'lodash/some';
import values from 'lodash/values';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import PreviewStep from './steps/preview';

const hasErrors = ( tree ) => {
	if ( ! tree ) {
		return false;
	}
	if ( isArray( tree ) ) {
		return some( tree, hasErrors );
	}
	if ( isPlainObject( tree ) ) {
		return some( values( tree ), hasErrors );
	}
	return true;
};

const PrintLabelDialog = ( props ) => {
	const currencySymbol = props.storeOptions.currency_symbol;

	const getTotalCost = () => {
		const ratesInfo = props.form.rates;
		const ratesCost = ratesInfo.values.map( ( rateId, index ) => ratesInfo.available[ index ][ rateId ].rate );
		return sum( ratesCost ).toFixed( 2 );
	};

	const canPurchase = ! props.form.isSubmitting && ! hasErrors( props.errors );
	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal wcc-shipping-label-dialog" >
			<div className="wcc-shipping-label-dialog__content">
				<h3>
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
						label: __( 'Purchase' ) + ( canPurchase ? ' (' + currencySymbol + getTotalCost() + ')' : '' ),
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
