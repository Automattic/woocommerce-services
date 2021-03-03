/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';

const Indicator = ( { title, subtitle, state, message, children } ) => {
	let icon, className;
	switch ( state ) {
		case 'success':
			className = 'is-success';
			icon = 'checkmark-circle';
			break;
		case 'warning':
			className = 'is-warning';
			icon = 'notice';
			break;
		case 'error':
		default:
			className = 'is-error';
			icon = 'notice';
	}

	return (
		<FormFieldset>
			<FormLegend>
				<span>{ title }</span>
				{ subtitle && ( <span className="plugin-status__indicator-subtitle">{ subtitle }</span> ) }
			</FormLegend>
			<div className={ classNames( 'plugin-status__indicator', className ) }>
				<div className="plugin-status__indicator-icon-and-message">
					<div className="plugin-status__indicator-icon">
						<Gridicon icon={ icon } />
					</div>
					<div className="plugin-status__indicator-message">
						{ message }
					</div>
				</div>
				{ children }
			</div>
		</FormFieldset>
	);
};

Indicator.propTypes = {
	title: PropTypes.string,
	subtitle: PropTypes.string,
	state: PropTypes.string,
	message: PropTypes.string,
	children: PropTypes.node,
};

export default Indicator;
