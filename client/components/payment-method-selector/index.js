import React, { PropTypes } from 'react';
import FieldDescription from 'components/field-description';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PaymentMethod from '../payment-method';
import sanitizeHTML from 'lib/utils/sanitize-html';
import { translate as __ } from 'lib/mixins/i18n';

const PaymentMethodSelector = ( { paymentMethods, title, description, value, setValue } ) => {
	const renderNoMethods = () => (
		<p>
			{ __( 'No payment methods available' ) }
		</p>
	);

	const renderPaymentMethods = () => (
		paymentMethods.map( ( paymentMethod ) => {
			const methodValue = parseInt( paymentMethod.payment_method_id, 10 );
			return (
				<PaymentMethod
					key={ paymentMethod.payment_method_id }
					value={ methodValue }
					currentValue={ value }
					setValue={ setValue }
					name={ paymentMethod.name }
					cardType={ paymentMethod.card_type }
					cardDigits={ paymentMethod.card_digits }
					expiry={ paymentMethod.expiry }
				/>
			);
		} )
	);

	const hasMethods = 0 < paymentMethods.length;

	return (
		<FormFieldset>
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FieldDescription text={ description } />
			{ hasMethods ? renderPaymentMethods() : renderNoMethods() }
		</FormFieldset>
	);
};

PaymentMethodSelector.propTypes = {
	description: PropTypes.string,
	paymentMethods: PropTypes.array.isRequired,
	title: PropTypes.string,
	value: PropTypes.number.isRequired,
	setValue: PropTypes.func.isRequired,
};

export default PaymentMethodSelector;
