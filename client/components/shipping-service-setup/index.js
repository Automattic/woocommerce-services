import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';

const ShippingServiceSetup = React.createClass( {
	displayName: 'ShippingServiceSetup',
	propTypes: {
		titlePlaceholder: React.PropTypes.string,
		titleValue: React.PropTypes.string,
		onChange: React.PropTypes.func,
	},
	render: function() {
		return (
			<div>
				<FormFieldset>
					<FormLabel htmlFor="title">Shipping method title</FormLabel>
					<FormTextInput id="title" name="title" placeholder={ this.props.titlePlaceholder } value={ this.props.titleValue } onChange={ this.props.onChange } />
					<FormSettingExplanation>The customer will see this during checkout</FormSettingExplanation>
				</FormFieldset>
				{ this.props.children }
			</div>
		);
	}
} );

export default ShippingServiceSetup;
