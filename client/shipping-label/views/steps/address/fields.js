import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import TextField from 'components/text-field';
import CountryDropdown from 'components/country-dropdown';
import StateDropdown from 'components/state-dropdown';

const AddressFields = ( { values, allowChangeCountry, group, labelActions, storeOptions, errors } ) => {
	const getId = ( fieldName ) => group + '_' + fieldName;
	const getValue = ( fieldName ) => values[ fieldName ] || '';
	const updateValue = ( fieldName, newValue ) => labelActions.updateAddressValue( group, fieldName, newValue );

	return (
		<div>
			<TextField
				id={ getId( 'name' ) }
				title={ __( 'Name' ) }
				value={ getValue( 'name' ) }
				updateValue={ ( value ) => updateValue( 'name', value ) }
				error={ errors.name } />
			<TextField
				id={ getId( 'company' ) }
				title={ __( 'Company' ) }
				value={ getValue( 'company' ) }
				updateValue={ ( value ) => updateValue( 'company', value ) }
				error={ errors.company } />
			<TextField
				id={ getId( 'address' ) }
				title={ __( 'Address' ) }
				value={ getValue( 'address' ) }
				updateValue={ ( value ) => updateValue( 'address', value ) }
				error={ errors.address } />
			<TextField
				id={ getId( 'address_2' ) }
				value={ getValue( 'address_2' ) }
				updateValue={ ( value ) => updateValue( 'address_2', value ) }
				error={ errors.address_2 } />
			<TextField
				id={ getId( 'city' ) }
				title={ __( 'City' ) }
				value={ getValue( 'city' ) }
				updateValue={ ( value ) => updateValue( 'city', value ) }
				error={ errors.city } />
			<StateDropdown
				id={ getId( 'state' ) }
				title={ __( 'State' ) }
				value={ getValue( 'state' ) }
				countryCode={ getValue( 'country' ) }
				countriesData={ storeOptions.countriesData }
				updateValue={ ( value ) => updateValue( 'state', value ) }
				error={ errors.state } />
			<TextField
				id={ getId( 'postcode' ) }
				title={ __( 'Postal code' ) }
				value={ getValue( 'postcode' ) }
				updateValue={ ( value ) => updateValue( 'postcode', value ) }
				error={ errors.postcode } />
			<CountryDropdown
				id={ getId( 'country' ) }
				title={ __( 'Country' ) }
				value={ getValue( 'country' ) }
				disabled={ ! allowChangeCountry }
				countriesData={ storeOptions.countriesData }
				updateValue={ ( value ) => updateValue( 'country', value ) }
				error={ errors.country } />
		</div>
	);
};

AddressFields.propTypes = {
	values: PropTypes.object.isRequired,
	allowChangeCountry: PropTypes.bool.isRequired,
	group: PropTypes.string.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
};

export default AddressFields;
