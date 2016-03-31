import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

const ShippingServiceEntry = ( { enabled, title, adjustment, adjustment_type, currencySymbol, onChange } ) => (
	<div className="wcc-shipping-service-entry">
		<FormCheckbox checked={ enabled } onChange={ onChange } />
		<span className="wcc-shipping-service-entry-title">{ title }</span>
		<FormTextInput id="adjustment" name="adjustment" placeholder="" value={ adjustment } onChange={ onChange }/>
		<FormSelect id="adjustment" value={ adjustment_type } onChange={ onChange }>
			<option value="flat">{ currencySymbol }</option>
			<option value="percentage">%</option>
		</FormSelect>
	</div>
);

ShippingServiceEntry.propTypes = {
	enabled: React.PropTypes.bool.isRequired,
	title: React.PropTypes.string.isRequired,
	adjustment: React.PropTypes.number.isRequired,
	adjustment_type: React.PropTypes.string.isRequired,
	currencySymbol: React.PropTypes.string.isRequired,
	onChange: React.PropTypes.func.isRequired,
};

export default ShippingServiceEntry;
