import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import TextField from 'components/text-field';
import { translate as __ } from 'lib/mixins/i18n';

const StateDropdown = ( props ) => {
	const statesMap = props.layout.dataset[ props.countryCode ];

	if ( ! statesMap ) { // We don't have a list of states for this country
		return (
			<TextField
				{ ...props }
				placeholder={ props.layout.placeholder }
				required={ false }
			/>
		);
	}

	if ( ! Object.keys( statesMap ).length ) { // This country has no states
		return null;
	}

	return (
		<Dropdown
			{ ...props }
			layout={ { titleMap: { '': __( 'Select one...' ), ...statesMap } } }
			/>
	);
};

StateDropdown.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default StateDropdown;
