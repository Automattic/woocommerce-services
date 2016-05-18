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
	const classes = classNames( 'wcc-shipping-service-entry', {
		'wcc-error': hasError,
	} );

	return (
		<div className={ classes }>
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
				const value = event.target.value ? event.target.value : 0;
				if ( ! isNaN( value ) ) {
					updateValue( 'adjustment', Number.parseFloat( value ) );
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
