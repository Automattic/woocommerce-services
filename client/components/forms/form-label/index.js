/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { localize } from 'i18n-calypso';

const FormLabel = ( { children, required, optional, translate, className, moment, numberFormat, ...extraProps } ) => {
	return (
		<label
			{ ...extraProps }
			className={ classnames( className, 'form-label' ) }
		>
			{ children }
			{required && ( <small className="form-label__required">{ translate( 'Required' ) }</small> ) }
			{optional && ( <small className="form-label__optional">{ translate( 'Optional' ) }</small> )}
		</label>
	);
};

FormLabel.propTypes = {
	required: PropTypes.bool,
	optional: PropTypes.bool,
	children: PropTypes.node,
};

export default localize( FormLabel );
