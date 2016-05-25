import React, { PropTypes } from 'react';
import FormCheckbox from 'components/forms/form-checkbox';
import FormSelect from 'components/forms/form-select';
import FormTextInput from 'components/forms/form-text-input';
import Gridicon from 'components/gridicon';
import classNames from 'classnames';

const ShippingServiceEntry = ( {
	enabled,
	title,
	adjustment,
	adjustment_type,
	currencySymbol,
	updateValue,
	hasError,
} ) => {
	return (
		<div className={ classNames( 'wcc-shipping-service-entry', { 'wcc-error': hasError } ) }>
			<label className="wcc-shipping-service-entry-title">
				<FormCheckbox
					checked={ enabled }
					onChange={ ( event ) => updateValue( 'enabled', event.target.checked ) }
				/>
				{ title }
			</label>
			{ hasError ? <Gridicon icon="notice" /> : null }
			<FormTextInput
				disabled={ ! enabled }
				value={ adjustment }
				onChange={ ( event ) => {
					const value = event.target.value || null;
					const floatValue = Number.parseFloat( value );

					/*
					 * If the adjustment value isn't a valid float, or ends in a non-integer, pass
					 * the value through unmodified and let the schema validation catch it.
					 *
					 * If the adjustment *is* a valid float, update the settings value with the
					 * parsed version so it doesn't fail schema validation.
					 */
					if ( isNaN( floatValue ) || value.match( /.*[^\d]$/ ) ) {
						updateValue( 'adjustment', value );
					} else {
						updateValue( 'adjustment', floatValue );
					}
				} }
				isError={ hasError }
			/>
			<FormSelect
				disabled={ ! enabled }
				value={ adjustment_type }
				onChange={ ( event ) => updateValue( 'adjustment_type', event.target.value ) }
			>
				<option value="flat">{ currencySymbol }</option>
				<option value="percentage">%</option>
			</FormSelect>
		</div>
	);
}

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
