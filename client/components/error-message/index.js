/**
 * External dependencies
 */
import React from 'react';

export default function ErrorMessage( { children } ) {
	return (
		<p className="error-message">
			{ children }
		</p>
	);
}
