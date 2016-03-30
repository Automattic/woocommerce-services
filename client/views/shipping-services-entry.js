import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

const ShippingServiceEntry = ( { enabled, title, adjustment, adjustmentType } ) => (
	<div className="wcc-shipping-service-entry">
		<FormCheckbox checked={ enabled } readOnly={ true } />
		<span className="wcc-shipping-service-entry-title">{ title }</span>
		<FormTextInput id="adjustment" name="adjustment" placeholder="" value={ adjustment } readOnly={ true }/>
		<FormSelect id="adjustment" value={ adjustmentType } readOnly={ true }>
			<option value="flat" readOnly={ true }>$</option>
			<option value="percentage" readOnly={ true }>%</option>
		</FormSelect>
	</div>
);

ShippingServiceEntry.propTypes = {
	enabled: React.PropTypes.bool.isRequired,
	title: React.PropTypes.string.isRequired,
	adjustment: React.PropTypes.number.isRequired,
	adjustmentType: React.PropTypes.string.isRequired,
};

export default ShippingServiceEntry;
