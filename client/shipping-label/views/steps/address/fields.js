import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import TextField from 'components/text-field';
import CountryDropdown from 'components/country-dropdown';
import StateDropdown from 'components/state-dropdown';

const AddressFields = ( { form, group, labelActions, storeOptions } ) => {
	const getId = ( fieldName ) => group + '_' + fieldName;
	const getValue = ( fieldName ) => form[ group ].values[ fieldName ];
	const updateValue = ( fieldName, newValue ) => labelActions.updateAddressValue( group, fieldName, newValue );

	return (
		<div>
			<TextField
				id={ getId( 'name' ) }
				title={ __( 'Name' ) }
				value={ getValue( 'name' ) }
				updateValue={ ( value ) => updateValue( 'name', value ) } />
			<TextField
				id={ getId( 'company' ) }
				title={ __( 'Company' ) }
				value={ getValue( 'company' ) }
				updateValue={ ( value ) => updateValue( 'company', value ) } />
			<TextField
				id={ getId( 'address' ) }
				title={ __( 'Address' ) }
				value={ getValue( 'address' ) }
				updateValue={ ( value ) => updateValue( 'address', value ) } />
			<TextField
				id={ getId( 'address_2' ) }
				value={ getValue( 'address_2' ) }
				updateValue={ ( value ) => updateValue( 'address_2', value ) } />
			<StateDropdown
				id={ getId( 'state' ) }
				title={ __( 'State' ) }
				value={ getValue( 'state' ) }
				countryCode={ getValue( 'country' ) }
				countriesData={ storeOptions.countriesData }
				updateValue={ ( value ) => updateValue( 'state', value ) } />
			<TextField
				id={ getId( 'postcode' ) }
				title={ __( 'Postal code' ) }
				value={ getValue( 'postcode' ) }
				updateValue={ ( value ) => updateValue( 'postcode', value ) } />
			<CountryDropdown
				id={ getId( 'country' ) }
				title={ __( 'Country' ) }
				value={ getValue( 'country' ) }
				disabled={ ! form[ group ].allowChangeCountry }
				countriesData={ storeOptions.countriesData }
				updateValue={ ( value ) => updateValue( 'country', value ) } />
		</div>
	);
};

export default AddressFields;
