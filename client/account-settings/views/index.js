import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ActionButtons from 'components/action-buttons';
import CompactCard from 'components/card/compact';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import PaymentMethodSelector from './payment-method-selector';
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'lib/mixins/i18n';
import * as actions from '../state/actions';
import * as NoticeActions from 'state/notices/actions';

const AccountSettingsRootView = ( props ) => {
	const onPaymentMethodChange = ( value ) => props.actions.setFormDataValue( 'selected_payment_method_id', value );

	const onSaveSuccess = () => {
		props.actions.setFormMetaProperty( 'pristine', true );
		props.noticeActions.successNotice( __( 'Your payment method has been updated.' ), { duration: 2250 } );
	};
	const onSaveFailure = () => props.noticeActions.errorNotice( __( 'Unable to update your payment method. Please try again.' ), { duration: 7000 } );
	const onSaveChanges = () => props.actions.saveForm( onSaveSuccess, onSaveFailure );

	const paymentMethodDescriptionFormat = __( 'Manage your payment methods on %(startLink)sWordPress.com%(endLink)s' );
	const paymentMethodDescription = sprintf(
		paymentMethodDescriptionFormat,
		{
			startLink: '<a href="https://wordpress.com/me/billing" target="_blank">',
			endLink: '</a>',
		}
	);

	const buttons = [
		{
			label: __( 'Save changes' ),
			onClick: onSaveChanges,
			isPrimary: true,
			isDisabled: props.formMeta.pristine || props.formMeta.isSaving,
		},
	];

	return (
		<div className="wcc-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<CompactCard>
				<PaymentMethodSelector
					description={ paymentMethodDescription }
					paymentMethods={ props.formMeta.payment_methods }
					onChange={ onPaymentMethodChange }
					title={ __( 'Payment Method' ) }
					value={ props.formData.selected_payment_method_id }
				/>
			</CompactCard>
			<CompactCard className="save-button-bar">
				<ActionButtons
					buttons={ buttons }
				/>
			</CompactCard>
		</div>
	);
};

AccountSettingsRootView.propTypes = {
	saveForm: PropTypes.func,
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return {
		formData: state.form.data,
		formMeta: state.form.meta,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		actions: bindActionCreators( actions, dispatch ),
		noticeActions: bindActionCreators( NoticeActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( AccountSettingsRootView );
