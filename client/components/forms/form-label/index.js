/** @format */

/**
 * External dependencies
 */

import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { localize } from 'i18n-calypso';

const FormLabel = ( { children: childrenProp, required, optional, translate, className, moment, numberFormat, dangerouslySetInnerHTML, ...extraProps } ) => {
	const children = dangerouslySetInnerHTML ? null : (
		<>
			{ childrenProp }
			{/* eslint-disable-next-line wpcalypso/jsx-classname-namespace */}
			{ required && ( <small className="form-label__required">{ translate( 'Required' ) }</small> ) }
			{/* eslint-disable-next-line wpcalypso/jsx-classname-namespace */}
			{ optional && ( <small className="form-label__optional">{ translate( 'Optional' ) }</small> ) }
		</>
	);

	return (
		<label
			{ ...extraProps }
			/* eslint-disable-next-line react/no-danger */
			dangerouslySetInnerHTML={ dangerouslySetInnerHTML }
			className={ classnames( className, 'form-label' ) }
		>
			{ children }
		</label>
	);
};

FormLabel.propTypes = {
	required: PropTypes.bool,
	optional: PropTypes.bool,
	children: PropTypes.node,
};

export default localize( FormLabel );
