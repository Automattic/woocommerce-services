import React, { PropTypes } from 'react';
import classNames from 'classnames';
import Gridicon from 'gridicons';
import _ from 'lodash';

const Checkbox = ( props ) => {
	const otherProps = _.omit( props, 'className' );
	return (
		<span className={ classNames( props.className, 'form-checkbox', { 'is-disabled': props.disabled } ) } >
			<input { ...otherProps } type="checkbox" />
			{ props.checked && <Gridicon icon="checkmark" size={ 14 } /> }
		</span>
	);
};

export default Checkbox;
