/* eslint-disable react/no-danger */
/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import Gridicon from 'gridicons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import sanitizeHTML from 'lib/utils/sanitize-html';

const renderLastUpdated = ( lastUpdated ) => {
	if ( ! lastUpdated ) {
		return null;
	}

	return (
		<FormSettingExplanation dangerouslySetInnerHTML={ sanitizeHTML( lastUpdated ) } />
	);
};

const Indicator = ( { icon, className, message, lastUpdated } ) => {
	return (
		<div className={ classNames( 'indicators__indicator', className ) }>
			<div className="indicators__indicator-icon-and-message">
				<div className="indicators__indicator-icon">
					<Gridicon icon={ icon } />
				</div>
				<div className="indicators__indicator-message">
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
