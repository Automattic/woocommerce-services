import React, { PropTypes } from 'react';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';

// TODO - create PaymentMethod like https://github.com/Automattic/wp-calypso/blob/29984e5677960bdba231577a253cf864d4f17796/client/my-sites/upgrades/checkout/stored-card.jsx
// or https://github.com/Automattic/wp-calypso/tree/29984e5677960bdba231577a253cf864d4f17796/client/my-sites/upgrades/checkout

const PaymentMethod = ( { value, currentValue, setValue, name, cardType, cardDigits, expiry } ) => {
	const cardClasses = 'payment-method ' + cardType.toLowerCase();

	return (
		<FormLabel>
			<FormRadio value={ value } checked={ value === currentValue } onChange={ () => setValue( value ) } />
			<div className={ cardClasses }>
				<span className="payment-method__number">{ cardType } ****{ cardDigits }</span>
				<span className="payment-method__name">{ name }</span>
				<span className="payment-method__expiration-date">
					{ expiry }
				</span>
			</div>
		</FormLabel>
	);
};

PaymentMethod.propTypes = {
	currentValue: PropTypes.number.isRequired,
	cardDigits: PropTypes.string,
	cardType: PropTypes.string.isRequired,
	expiry: PropTypes.string,
	name: PropTypes.string,
	setValue: PropTypes.func.isRequired,
	value: PropTypes.number.isRequired,
};

export default PaymentMethod;
