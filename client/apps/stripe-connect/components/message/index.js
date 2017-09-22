/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */

const Message = ( { message } ) => {
	return (
		<div>
			<strong>Message</strong>
			<div>
				{ message }
			</div>
		</div>
	);
};

Message.propTypes = {
	message: PropTypes.string.isRequired,
};

function mapStateToProps( state ) {
	return {
		message: state.message,
	};
}

function mapDispatchToProps() {
	return {};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( Message );
