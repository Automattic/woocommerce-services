import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import TextField from 'components/text-field';

const StateDropdown = ( props ) => {
	const statesMap = props.layout.dataset[ props.countryCode ];

	if ( ! statesMap || ! Object.keys( statesMap ).length ) {
		return (
			<TextField
				{ ...props }
				placeholder={ props.layout.placeholder }
				required={ false }
			/>
		);
	}

	return (
		<Dropdown
			{ ...props }
			layout={ { titleMap: statesMap } }
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
