import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';

// TODO - proper markup
const Indicator = ( { state, stateMessage, lastUpdated } ) => {
	return (
		<div>
			<span>{ state }</span>
			<span>{ stateMessage }</span>
			<span>{ lastUpdated }</span>
		</div>
	);
};

const Indicators = ( { schema, indicators } ) => {
	return (
		<FormFieldset>
			<FormLegend>{ schema.title }</FormLegend>
			{ indicators.map( indicator => (
				<Indicator
					key={ indicator.id }
					state={ indicator.state }
					stateMessage={ indicator.state_message }
					lastUpdated={ indicator.last_updated }
				/>
			) ) }
		</FormFieldset>
	);
};

Indicators.propTypes = {
	schema: PropTypes.object.isRequired,
	indicators: PropTypes.array.isRequired,
};

export default Indicators;
