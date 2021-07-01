/** @format */

/**
 * External dependencies
 */

import React from 'react'
import classNames from 'classnames'
import './styles.scss'

const FormSelect = ({ inputRef, className, isError, ...restProps }) => {
	return (
		<select
			{...restProps}
			ref={inputRef}
			className={classNames(className, 'form-select', {
				'is-error': isError,
			})}
		/>
	)
}

export default FormSelect
