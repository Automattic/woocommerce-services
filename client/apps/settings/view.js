/**
 * External dependencies
 */
import React, { Component } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import SettingsForm from './components/settings-form';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { isLoaded, isFetching, isFetchError, getFormSchema, getStoreOptions, getFormLayout } from './state/selectors';
import { fetchForm } from './state/actions';

class Settings extends Component {
	componentWillMount() {
		if ( ! this.props.loaded && ! this.props.isFetching && ! this.props.isFetchError ) {
			this.props.fetchForm();
		}
	}

	componentWillReceiveProps( props ) {
		if ( ! props.loaded && ! props.isFetching && ! props.isFetchError ) {
			this.props.fetchForm();
		}
	}

	render() {
		return (
			<div>
				<GlobalNotices id="notices" notices={ notices.list } />
				<SettingsForm
					{ ...this.props }
				/>
			</div>
		);
	}
}

export default connect(
	( state ) => ( {
		isFetching: isFetching( state ),
		isFetchError: isFetchError( state ),
		loaded: isLoaded( state ),
		storeOptions: getStoreOptions( state ),
		schema: getFormSchema( state ),
		layout: getFormLayout( state ),
	} ),
	( dispatch ) => bindActionCreators( {
		fetchForm,
	}, dispatch )
)( Settings );
