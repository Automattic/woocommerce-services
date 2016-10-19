import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';
import _ from 'lodash';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import Notice from 'components/notice';
import FormButton from 'components/forms/form-button';

const RadioButton = ( props ) => {
	return (
		<FormLabel className={ classNames( 'suggestion', { 'is-selected': props.checked } ) }>
			<FormRadio { ..._.omit( props, 'children' ) } />
			{ props.children }
		</FormLabel>
	);
};

const AddressSummary = ( { values, originalValues, countriesData } ) => {
	originalValues = originalValues || {};
	const { state, country } = values;

	let stateStr = '';
	if ( state ) {
		const statesMap = ( countriesData[ country ] || {} ).states || {};
		stateStr = statesMap[ state ] || state;
	}
	const countryStr = countriesData[ country ].name;

	const getValue = ( fieldName ) => {
		const rawValue = values[ fieldName ];
		if ( ! rawValue ) {
			return '';
		}
		const originalValue = originalValues[ fieldName ];
		const highlight = originalValue && originalValue.toLowerCase() !== rawValue.toLowerCase();
		let value = rawValue;
		switch ( fieldName ) {
			case 'state':
				value = stateStr;
				break;
			case 'country':
				value = countryStr;
		}
		return <span className={ highlight ? 'highlight' : '' }>{ value }</span>;
	};

	return (
		<div className="summary">
			<p>{ getValue( 'name' ) }</p>
			<p>{ getValue( 'address' ) } { getValue( 'address_2' ) }</p>
			<p>{ getValue( 'city' ) }, { getValue( 'postcode' ) } { getValue( 'state' ) }</p>
			<p>{ getValue( 'country' ) }</p>
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
			<Notice
				className="validation-message"
				status="is-warning"
				showDismiss={ false }
				text={ __( 'We have slightly modified the address entered. If correct, please use the suggested address to ensure accurate delivery.' ) } />
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
						originalValues={ values }
						countriesData={ countriesData } />
				</RadioButton>
			</div>
			<div className="step__confirmation-container">
				<FormButton
					type="button"
					className="step__confirmation"
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
