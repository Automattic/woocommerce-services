/**
 * External dependencies
 */
import React from 'react';

export default ( { email, country, createLink, connectLink } ) => ( {
	View: () => (
		<div>
			<strong>Connect to Stripe</strong>
			<p>
				Connect using the following email and country: { email } / { country } <a href={ createLink}>create account</a>
			</p>
			<p>
				<a href={ connectLink }>connect existing account</a>
			</p>
		</div>
	),
} );
