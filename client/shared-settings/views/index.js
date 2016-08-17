import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import CompactCard from 'components/card/compact';
import Button from 'components/button';
import PaymentMethodSelector from 'components/payment-method-selector';
import { translate as __ } from 'lib/mixins/i18n';
import * as SharedSettingsActions from 'shared-settings/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';

const SharedSettingsRootView = ( props ) => {
	const setValue = ( ) => {
	};

	return (
		<div className="wcc-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<CompactCard>
				<PaymentMethodSelector
					paymentMethods={ props.formMeta.payment_methods }
					title={ __( 'Payment Method' ) }
					description={ __( 'Used for purchases of shipping labels. Add or edit your payment methods on WordPress.com' ) }
					value={ props.formData.selected_payment_method_id ? props.formData.selected_payment_method_id : 0 }
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
