/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

/**
 * Internal dependencies
 */
import * as StripeActions from '../../state/actions';

const StripeConnect = ( { email, country, createAccount, startOauth, setEmail, setCountry } ) => {
	const updateEmail = ( e ) => setEmail( e.target.value );
	const updateCountry = ( e ) => setCountry( e.target.value );

	return (
		<div>
			<strong>Connect to Stripe</strong>
			<div>
				Create a stripe account using the following:
			</div>
			<div>
				email:
				<input type="text" id="wcs_stripe_email" onChange={ updateEmail } value={ email } />
			</div>
			<div>
				country:
				<input type="text" id="wcs_stripe_country" onChange={ updateCountry } value={ country } />
			</div>
			<div>
				<input type="button" value="Create Account" onClick={ createAccount } />
				<input type="button" value="I already have an account" onClick={ startOauth } />
			</div>
		</div>
	);
};

StripeConnect.propTypes = {
	email: PropTypes.string.isRequired,
	country: PropTypes.string.isRequired,
	createAccount: PropTypes.func.isRequired,
	startOauth: PropTypes.func.isRequired,
	setEmail: PropTypes.func.isRequired,
	setCountry: PropTypes.func.isRequired,
};

function mapStateToProps( state ) {
	return {
		email: state.email,
		country: state.country,
	};
}

function mapDispatchToProps( dispatch ) {
	return bindActionCreators( StripeActions, dispatch );
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( StripeConnect );
