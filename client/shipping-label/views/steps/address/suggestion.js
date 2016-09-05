import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';
import omit from 'lodash/omit';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import FormButton from 'components/forms/form-button';

const RadioButton = ( props ) => {
	return (
		<FormLabel className={ classNames( 'suggestion', { 'is-selected': props.checked } ) }>
			<FormRadio { ...omit( props, 'children' ) } />
			{ props.children }
		</FormLabel>
	);
};

const AddressSummary = ( { values, countriesData } ) => {
	const { name, address, address_2, city, postcode, state, country } = values;
	let stateStr = '';
	if ( state ) {
		const statesMap = ( countriesData[ country ] || {} ).states || {};
		stateStr = statesMap[ state ] || state;
	}
	const countryStr = countriesData[ country ].name;
	return (
		<div className="summary">
			<p>{ name }</p>
			<p>{ `${address} ${address_2}`.trim() }</p>
			<p>{ `${city}, ${postcode} ${stateStr}`.trim() }</p>
			<p>{ countryStr }</p>
		</div>
	);
};

const AddressSuggestion = ( {
		values,
		normalized,
		selectNormalized,
		selectNormalizedAddress,
		editAddress,
		confirmAddressSuggestion,
		countriesData,
	} ) => {
	return (
		<div>
			<div className="validation-message">{ __( 'We have slightly modified the entered address. If that looks correct, please use the suggested address to ensure accurate delivery.' ) }</div>
			<div className="suggestion-container">
				<RadioButton
					checked={ ! selectNormalized }
					onChange={ () => selectNormalizedAddress( false ) } >
					<span className="suggestion-title">{ __( 'Address entered' ) }</span>
					<AddressSummary
						values={ values }
						countriesData={ countriesData } />
					<a className="suggestion-edit" onClick={ editAddress } >
						{ __( 'Edit address' ) }
					</a>
				</RadioButton>
				<RadioButton
					checked={ selectNormalized }
					onChange={ () => selectNormalizedAddress( true ) } >
					<span className="suggestion-title">{ __( 'Suggested address' ) }</span>
					<AddressSummary
						values={ normalized }
						countriesData={ countriesData } />
				</RadioButton>
			</div>
			<div className="address__confirmation-container">
				<FormButton
					type="button"
					className="address__confirmation"
					onClick={ confirmAddressSuggestion }
					isPrimary >
					{ __( 'Use selected address' ) }
				</FormButton>
			</div>
		</div>
	);
};

AddressSuggestion.propTypes = {
	values: PropTypes.object.isRequired,
	normalized: PropTypes.object,
	selectNormalized: PropTypes.bool.isRequired,
	selectNormalizedAddress: PropTypes.func.isRequired,
	confirmAddressSuggestion: PropTypes.func.isRequired,
	editAddress: PropTypes.func.isRequired,
	countriesData: PropTypes.object.isRequired,
};

export default AddressSuggestion;
