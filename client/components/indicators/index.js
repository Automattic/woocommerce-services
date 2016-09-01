import React, { PropTypes } from 'react';
import classNames from 'classnames';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import Gridicon from 'components/gridicon';
import sanitizeHTML from 'lib/utils/sanitize-html';

const renderLastUpdated = ( lastUpdated ) => {
	if ( ! lastUpdated ) {
		return null;
	}

	return (
		<div className="form-setting-explanation indicator__last-updated">
			<span dangerouslySetInnerHTML={ sanitizeHTML( lastUpdated ) } />
		</div>
	);
};

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
			{ renderLastUpdated( lastUpdated ) }
		</div>
	);
};

const renderSubTitle = ( subtitle ) => {
	if ( ! subtitle ) {
		return null;
	}

	return (
		<span className="indicators__subtitle" dangerouslySetInnerHTML={ sanitizeHTML( subtitle ) } />
	);
};

const Indicators = ( { title, subtitle, indicators, className } ) => {
	return (
		<FormFieldset className={ className }>
			<FormLegend>
				<span dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
				{ renderSubTitle( subtitle ) }
			</FormLegend>
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
	title: PropTypes.string.isRequired,
	subtitle: PropTypes.string,
	indicators: PropTypes.array.isRequired,
	className: PropTypes.string,
};

export default Indicators;
