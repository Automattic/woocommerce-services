/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

/**
 * Internal dependencies
 */
import * as StripeActions from './state/actions';
import Loading from './components/loading';
import Connect from './components/loading';

const StripeConnect = ( { actions, stripeSettings } ) => {
	console.log( stripeSettings );
	if ( 'not_connected' === stripeSettings ) {
		return <Connect />;
	}

	if ( 'new' === stripeSettings.status ) {
		actions.fetchStripeSettings();
	}

	return <Loading />;
};

StripeConnect.propTypes = {
	actions: PropTypes.object.isRequired,
	stripeSettings: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return { stripeSettings: state };
}

function mapDispatchToProps( dispatch ) {
	return {
		actions: bindActionCreators( StripeActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( StripeConnect );
