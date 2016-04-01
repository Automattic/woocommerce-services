import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

const ShippingServiceEntry = ( { id, enabled, title, adjustment, adjustment_type, currencySymbol, onChange, onChangeScope } ) => (
	<div className="wcc-shipping-service-entry">
		<FormCheckbox
			name="enabled"
			checked={ enabled }
			onChange={ ( event ) => onChange( onChangeScope, id, event.target.name, event.target.checked ) }
		/>
		<span className="wcc-shipping-service-entry-title">{ title }</span>
		<FormTextInput
			name="adjustment"
			value={ adjustment }
			onChange={ ( event ) => onChange( onChangeScope, id, event.target.name, parseFloat( event.target.value ) ) }
		/>
		<FormSelect
			name="adjustment_type"
			value={ adjustment_type }
			onChange={ ( event ) => onChange( onChangeScope, id, event.target.name, event.target.value ) }
		>
			<option value="flat">{ currencySymbol }</option>
			<option value="percentage">%</option>
		</FormSelect>
	</div>
);

ShippingServiceEntry.propTypes = {
	id: PropTypes.string.isRequired,
	enabled: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	adjustment: PropTypes.number.isRequired,
	adjustment_type: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	onChangeScope: PropTypes.string.isRequired
};

export default ShippingServiceEntry;
