import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';

const CountryDropdown = ( props ) => {
	const titleMap = {};
	Object.keys( props.countriesData ).forEach( ( countryCode ) => {
		titleMap[ countryCode ] = props.countriesData[ countryCode ].name;
	} );
	return (
		<Dropdown
			{ ...props }
			layout={ { titleMap } }
			/>
	);
};

CountryDropdown.propTypes = {
	id: PropTypes.string.isRequired,
	countriesData: PropTypes.object.isRequired,
	layout: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	value: PropTypes.string.isRequired,
	updateValue: PropTypes.func.isRequired,
	error: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.bool,
	] ),
};

export default CountryDropdown;
