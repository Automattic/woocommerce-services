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
			<AddressStep.Origin
				{ ...props }
				{ ...props.origin }
				errors={ props.errors.origin } />
			<AddressStep.Destination
				{ ...props }
				{ ...props.destination }
				errors={ props.errors.destination } />
			<PackagesStep
				{ ...props }
				{ ...props.packages }
				errors={ props.errors.packages } />
			<RatesStep
				{ ...props }
				{ ...props.rates }
				errors={ props.errors.rates } />
			<PreviewStep
				{ ...props }
				{ ...props.preview }
				errors={ props.errors.preview } />
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
