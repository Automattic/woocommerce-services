import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormRadio from 'components/forms/form-radio';
import sanitizeHTML from 'lib/utils/sanitize-html';
import FieldDescription from 'components/field-description';

const PaymentMethod = ( { value, currentValue, setValue, name, cardType, cardDigits, expiry } ) => {
	return (
		<FormLabel>
			<FormRadio value={ value } checked={ value === currentValue } onChange={ () => setValue( value ) } />
			{ name } { cardType } { cardDigits } { expiry }
		</FormLabel>
	);
};

const PaymentMethodSelector = ( { paymentMethods, title, description, value, setValue } ) => {
	return (
		<FormFieldset>
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FieldDescription text={ description } />
			{ paymentMethods.map( ( paymentMethod ) => {
				return (
					<PaymentMethod
						key={ paymentMethod.payment_method_id }
						value={ paymentMethod.payment_method_id }
						currentValue={ value }
						setValue={ setValue }
						name={ paymentMethod.name }
						cardType={ paymentMethod.card_type }
						cardDigits={ paymentMethod.card_digits }
						expiry={ paymentMethod.expiry }
					/>
				);
			} ) }
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
