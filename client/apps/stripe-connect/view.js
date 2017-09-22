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
import Connect from './components/connect';
import Message from './components/message';

const StripeConnect = ( { actions, state } ) => {
	if ( state.message ) {
		return <Message />;
	}

	if ( 'loaded' === state.status ) {
		return <Connect />;
	}

	if ( 'new' === state.status ) {
		actions.fetchStripeSettings();
	}

	return <Loading />;
};

StripeConnect.propTypes = {
	actions: PropTypes.object.isRequired,
	state: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return { state: state };
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
