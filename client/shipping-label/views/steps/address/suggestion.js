import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';
import omit from 'lodash/omit';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';

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

const AddressSuggestion = ( { values, normalized, selectNormalized, selectNormalizedAddress, editAddress, countriesData } ) => {
	return (
		<div>
			<span>{ __( 'To ensure accurate delivery, we have slightly modified the address entered.' ) }</span>
			<div className="suggestion-container">
				<RadioButton
					checked={ ! selectNormalized }
					onChange={ () => selectNormalizedAddress( false ) } >
					<span className="suggestion-title">{ __( 'Address entered' ) }</span>
					<AddressSummary
						values={ values }
						countriesData={ countriesData } />
					<a onClick={ editAddress } >
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
		</div>
	);
};

AddressSuggestion.propTypes = {
	values: PropTypes.object.isRequired,
	normalized: PropTypes.object,
	selectNormalized: PropTypes.bool.isRequired,
	selectNormalizedAddress: PropTypes.func.isRequired,
	editAddress: PropTypes.func.isRequired,
	countriesData: PropTypes.object.isRequired,
};

export default AddressSuggestion;
