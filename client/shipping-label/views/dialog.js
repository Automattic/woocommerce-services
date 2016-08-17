import React from 'react';
import Dialog from 'components/dialog';
import ActionButtons from 'components/action-buttons';
import { translate as __ } from 'lib/mixins/i18n';
import sum from 'lodash/sum';
import AddressStep from './steps/address';
import PackagesStep from './steps/packages';
import RatesStep from './steps/rates';
import PreviewStep from './steps/preview';

const PrintLabelDialog = ( props ) => {
	const currencySymbol = props.storeOptions.currency_symbol;

	const getTotalCost = () => {
		const ratesInfo = props.form.rates;
		const ratesCost = ratesInfo.values.map( ( rateId, index ) => ratesInfo.available[ index ][ rateId ].rate );
		return sum( ratesCost ).toFixed( 2 );
	};

	const canPurchase = ! props.form.isSubmitting;
	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal wcc-shipping-label-dialog" >
			<AddressStep.Origin
				{ ...props }
				{ ...props.origin } />
			<AddressStep.Destination
				{ ...props }
				{ ...props.destination } />
			<PackagesStep
				{ ...props }
				{ ...props.packages } />
			<RatesStep
				{ ...props }
				{ ...props.rates } />
			<PreviewStep
				{ ...props }
				{ ...props.preview } />
			<ActionButtons buttons={ [
				{
					isDisabled: ! canPurchase,
					onClick: props.labelActions.purchaseLabel,
					isPrimary: true,
					label: __( 'Purchase' ) + ( canPurchase ? ' (' + currencySymbol + getTotalCost() + ')' : '' ),
				},
			] } />
		</Dialog>
	);
};

export default PrintLabelDialog;
