import React, { PropTypes } from 'react';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'lib/mixins/i18n';

// TODO - create PaymentMethod like https://github.com/Automattic/wp-calypso/blob/29984e5677960bdba231577a253cf864d4f17796/client/my-sites/upgrades/checkout/stored-card.jsx
// or https://github.com/Automattic/wp-calypso/tree/29984e5677960bdba231577a253cf864d4f17796/client/my-sites/upgrades/checkout

const PaymentMethod = ( { value, currentValue, onChange, name, cardType, cardDigits, expiry } ) => {
	const cardClasses = 'payment-method ' + cardType.toLowerCase();
	const expirationDate = sprintf( __( 'Expires %(monthAndYear)s' ),
		{
			monthAndYear: expiry,
		}
	);

	return (
		<FormLabel className="payment-method__label">
			<FormRadio
				className="payment-method__radio"
				value={ value }
				checked={ value === currentValue }
				onChange={ () => onChange( value ) }
			/>
			<div className={ cardClasses }>
				<span className="payment-method__number">{ cardType } ****{ cardDigits }</span>
				<span className="payment-method__name">{ name }</span>
				<span className="payment-method__expiration-date">
					{ expirationDate }
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
	onChange: PropTypes.func.isRequired,
	value: PropTypes.number.isRequired,
};

export default PaymentMethod;
