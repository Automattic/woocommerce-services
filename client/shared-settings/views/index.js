import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import CompactCard from 'components/card/compact';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import PaymentMethodSelector from 'components/payment-method-selector';
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'lib/mixins/i18n';
import * as SharedSettingsActions from 'shared-settings/state/actions';

const SharedSettingsRootView = ( props ) => {
	const setValue = ( ) => {
		// TODO
	};

	const paymentMethodDescriptionFormat = __( 'Manage your payment methods on %(startLink)sWordPress.com%(endLink)s' );
	const paymentMethodDescription = sprintf(
		paymentMethodDescriptionFormat,
		{
			startLink: '<a href="https://wordpress.com/me/billing" target="_blank">',
			endLink: '</a>',
		}
	);

	return (
		<div className="wcc-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<CompactCard>
				<PaymentMethodSelector
					paymentMethods={ props.formMeta.payment_methods }
					title={ __( 'Payment Method' ) }
					description={ paymentMethodDescription }
					value={ props.formData.selected_payment_method_id }
					setValue={ setValue }
				/>
			</CompactCard>
			<CompactCard className="save-button-bar">
				<Button>
					{ __( 'Save' ) }
				</Button>
			</CompactCard>
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
