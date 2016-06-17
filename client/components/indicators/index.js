import React, { PropTypes } from 'react';
import classNames from 'classnames';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import Gridicon from 'components/gridicon';
import { sanitize } from 'dompurify';

const renderLastUpdated = ( lastUpdated ) => {
	if ( ! lastUpdated ) {
		return null;
	}

	/* for dangerouslySetInnerHTML we need to pause linting for a narrow scope */
	/* eslint-disable */
	return (
		<div className="form-setting-explanation indicator__last-updated">
			<span dangerouslySetInnerHTML={ { __html: sanitize( lastUpdated, { ADD_ATTR: ['target'] } ) } } >
			</span>
		</div>
	);
	/* eslint-enable */
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
		<span className="indicators__subtitle">{ subtitle }</span>
	);
};

const Indicators = ( { schema, indicators } ) => {
	return (
		<FormFieldset>
			<FormLegend>{ schema.title }{ renderSubTitle( schema.subtitle ) }</FormLegend>
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
