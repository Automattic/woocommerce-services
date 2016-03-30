import React from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';

export default React.createClass( {
	displayName: 'ShippingServiceEntry',

	propTypes: {
		enabled: React.PropTypes.bool,
		title: React.PropTypes.string,
		adjustment: React.PropTypes.number,
		adjustmentType: React.PropTypes.string,
	},

	render: function() {
		return (
			<div className="wcc-shipping-service-entry">
				<FormCheckbox checked={ this.props.enabled } readOnly={ true } />
				<span className="wcc-shipping-service-entry-title">{ this.props.title }</span>
				<FormTextInput id="adjustment" name="adjustment" placeholder="" value={ this.props.adjustment } readOnly={ true }/>
				<FormSelect id="adjustment" value={ this.props.adjustmentType } readOnly={ true }>
					<option value="flat" readOnly={ true }>$</option>
					<option value="percentage" readOnly={ true }>%</option>
				</FormSelect>
			</div>
		);
	}
} );
