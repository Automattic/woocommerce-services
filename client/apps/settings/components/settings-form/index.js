/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import SettingsGroup from './settings-group';
import NuxNotice from 'components/nux-notice';
import * as FormActions from 'apps/settings/state/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as FormValueActions from 'apps/settings/state/values/actions';
import { getShippingSettingsForm } from 'apps/settings/state/selectors';
import getFormErrors from 'apps/settings/state/selectors/errors';
import LoadingSpinner from 'components/loading-spinner';

const SettingsForm = ( props ) => {
	if ( ! props.loaded ) {
		return (
			<LoadingSpinner />
		);
	}

	const renderGroup = ( index ) => {
		return (
			<SettingsGroup
				{ ...props }
				group={ props.layout[ index ] }
				saveForm={ props.formValueActions.submit }
				key={ index }
			/>
		);
	};

	return (
		<div>
			{ ! props.form.noticeDismissed && (
				<NuxNotice onDismiss={ props.formActions.dismissNotice } >
					{ __( 'Now add your zip code and choose which services you want to offer your customers.' ) }
				</NuxNotice>
			) }
			{ props.layout.map( ( group, idx ) => renderGroup( idx ) ) }
		</div>
	);
};

SettingsForm.propTypes = {
	loaded: PropTypes.bool.isRequired,
	storeOptions: PropTypes.object,
	schema: PropTypes.object,
	layout: PropTypes.array,
};

function mapStateToProps( state, props ) {
	return {
		form: getShippingSettingsForm( state ),
		errors: props.loaded && getFormErrors( state, props.schema ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		formValueActions: bindActionCreators( FormValueActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SettingsForm );
