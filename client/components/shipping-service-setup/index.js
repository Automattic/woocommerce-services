import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';

const ShippingServiceSetup = ( {
	titlePlaceholder,
	titleValue,
	onChange,
	children,
} ) => (
	<div>
		<FormFieldset>
			<FormLabel htmlFor="title">Shipping method title</FormLabel>
			<FormTextInput id="title" name="title" placeholder={ titlePlaceholder } value={ titleValue } onChange={ onChange } />
			<FormSettingExplanation>The customer will see this during checkout</FormSettingExplanation>
		</FormFieldset>
		{ children }
	</div>
);

ShippingServiceSetup.propTypes = {
	titlePlaceholder: React.PropTypes.string,
	titleValue: React.PropTypes.string,
	onChange: React.PropTypes.func,
};

export default ShippingServiceSetup;
