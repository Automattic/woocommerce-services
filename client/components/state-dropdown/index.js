import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import TextField from 'components/text-field';
import { translate as __ } from 'lib/mixins/i18n';

const StateDropdown = ( props ) => {
	const statesMap = ( props.countriesData[ props.countryCode ] || {} ).states;

	if ( ! statesMap ) { // We don't have a list of states for this country
		return (
			<TextField { ...props } />
		);
	}

	if ( ! Object.keys( statesMap ).length ) { // This country has no states
		return null;
	}

	return (
		<Dropdown
			{ ...props }
			valuesMap={ { '': __( 'Select one...' ), ...statesMap } }
			/>
	);
};

StateDropdown.propTypes = {
	id: PropTypes.string.isRequired,
	countriesData: PropTypes.object.isRequired,
	valuesMap: PropTypes.object.isRequired,
	title: PropTypes.string,
	description: PropTypes.string,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default StateDropdown;
