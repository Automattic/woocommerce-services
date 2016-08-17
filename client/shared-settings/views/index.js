import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
//import Button from 'components/button';
//import { translate as __ } from 'lib/mixins/i18n';
import * as SharedSettingsActions from 'shared-settings/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';

const SharedSettingsRootView = ( props ) => {
	return (
		<div className="wcc-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<span>
				{ props.formMeta.payment_methods.map( method => {
					return ( <span key={ method.name }> { method.name } { method.card_type } { method.card_digits } { method.expiry }</span> );
				} ) }
			</span>
		</div>
	);
};

SharedSettingsRootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return {
		formData: state.formData,
		formMeta: state.formMeta,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		actions: bindActionCreators( SharedSettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( SharedSettingsRootView );
