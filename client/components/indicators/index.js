import React, { PropTypes } from 'react';
import classNames from 'classnames';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import Gridicon from 'components/gridicon';

const Indicator = ( { icon, className, message, lastUpdated } ) => {
	return (
		<div className={ classNames( 'indicator', className ) }>
			<div className="indicator__icon-and-message">
				<div className="indicator__icon">
					<Gridicon icon={ icon }/>
				</div>
				<div className="indicator__message">
					{ message }
				</div>
			</div>
			{ lastUpdated
				? <div className="form-setting-explanation indicator__last-updated">
						<span>{ lastUpdated }</span>
					</div>
				: null }
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
					icon={ indicator.icon }
					className={ indicator.class }
					message={ indicator.message }
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
