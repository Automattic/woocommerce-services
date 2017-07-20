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
import WCCSettingsGroup from './settings-group';
import NuxNotice from 'components/nux-notice';
import * as FormActions from 'settings/state/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as FormValueActions from 'settings/state/values/actions';
import getFormErrors from 'settings/state/selectors/errors';

const SettingsForm = ( props ) => {
	const renderGroup = ( index ) => {
		return (
			<WCCSettingsGroup
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
					{ __( 'Now add your zip code and chose which services you want to offer your customers.' ) }
				</NuxNotice>
			) }
			{ props.layout.map( ( group, idx ) => renderGroup( idx ) ) }
		</div>
	);
};

SettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

function mapStateToProps( state, props ) {
	return {
		form: state.form,
		errors: getFormErrors( state, props.schema ),
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
