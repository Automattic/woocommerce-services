import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

const ShippingServiceEntry = ( {
	id,
	enabled,
	title,
	adjustment,
	adjustment_type,
	currencySymbol,
	onChange,
	settingsKey,
} ) => {
	const onCheckboxChange = ( event ) => onChange( settingsKey, id, event.target.name, event.target.checked );
	const onNumericChange = ( event ) => onChange( settingsKey, id, event.target.name, parseFloat( event.target.value ) );
	const onTextChange = ( event ) => onChange( settingsKey, id, event.target.name, event.target.value );

	return (
		<div className="wcc-shipping-service-entry">
			<FormCheckbox
				name="enabled"
				checked={ enabled }
				onChange={ ( event ) => onCheckboxChange( event ) }
			/>
			<span className="wcc-shipping-service-entry-title">{ title }</span>
			<FormTextInput
				name="adjustment"
				value={ adjustment }
				onChange={ ( event ) => onNumericChange( event ) }
			/>
			<FormSelect
				name="adjustment_type"
				value={ adjustment_type }
				onChange={ ( event ) => onTextChange( event ) }
			>
				<option value="flat">{ currencySymbol }</option>
				<option value="percentage">%</option>
			</FormSelect>
		</div>
	);
};

ShippingServiceEntry.propTypes = {
	id: PropTypes.string.isRequired,
	enabled: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	adjustment: PropTypes.number.isRequired,
	adjustment_type: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

ShippingServiceEntry.defaultProps = {
	enabled: false,
	adjustment: 0,
	adjustment_type: 'flat',
};

export default ShippingServiceEntry;
