import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as SettingsActions from 'state/settings/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { getFormErrors } from 'state/selectors';

const WCCSettingsForm = ( props ) => {
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ props.layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					{ ...props }
					group={ group }
					saveForm={ props.settingsActions.submit }
					key={ idx }
				/>
			) ) }
		</div>
	);
};

WCCSettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

function mapStateToProps( state, props ) {
	return {
		settings: state.settings,
		form: state.form,
		errors: getFormErrors( state, props ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
