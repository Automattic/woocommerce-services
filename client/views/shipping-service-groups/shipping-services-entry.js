import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

const ShippingServiceEntry = ( {
	enabled,
	title,
	adjustment,
	adjustment_type,
	currencySymbol,
	updateValue,
} ) => (
	<div className="wcc-shipping-service-entry">
		<FormCheckbox
			checked={ enabled }
			onChange={ ( event ) => updateValue( 'enabled', event.target.checked ) }
		/>
		<span className="wcc-shipping-service-entry-title">{ title }</span>
		<FormTextInput
			value={ adjustment }
			onChange={ ( event ) => updateValue( 'adjustment', event.target.value ) }
		/>
		<FormSelect
			value={ adjustment_type }
			onChange={ ( event ) => updateValue( 'adjustment_type', event.target.value ) }
		>
			<option value="flat">{ currencySymbol }</option>
			<option value="percentage">%</option>
		</FormSelect>
	</div>
);

ShippingServiceEntry.propTypes = {
	enabled: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	adjustment: PropTypes.number.isRequired,
	adjustment_type: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

ShippingServiceEntry.defaultProps = {
	enabled: false,
	adjustment: 0,
	adjustment_type: 'flat',
};

export default ShippingServiceEntry;
