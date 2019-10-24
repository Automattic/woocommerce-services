/** @format */

/**
 * External dependencies
 */

import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

/**
 * Internal dependencies
 */
import { fetchLocations } from 'woocommerce/state/sites/data/locations/actions';
import { areLocationsLoading, areLocationsLoaded } from 'woocommerce/state/sites/data/locations/selectors';

class QueryLocations extends Component {
	static propTypes = {
		fetchLocations: PropTypes.func,
		siteId: PropTypes.number.isRequired,
		loadingLocations: PropTypes.bool.isRequired,
		locationsLoaded: PropTypes.bool.isRequired,
	};

	fetch = () => {
		const { fetchLocations: _fetchLocations, loadingLocations, locationsLoaded,  siteId } = this.props;
		( ! loadingLocations && ! locationsLoaded ) && _fetchLocations( siteId );
	}

	componentDidMount = () => {
		this.fetch();
	};

	componentDidUpdate = () => {
		this.fetch();
	};

	render = () => {
		return null;
	};
}

export default connect(
	state => ( 
		{ 
			loadingLocations: areLocationsLoading( state ),
			locationsLoaded: areLocationsLoaded( state ),
		} ),
	dispatch => bindActionCreators( { fetchLocations }, dispatch )
)( QueryLocations );
