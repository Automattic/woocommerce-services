import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';
import PrintLabelDialog from './dialog';
import * as ShippingLabelActions from 'shipping-label/state/actions';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';

const ShippingLabelRootView = ( props ) => {
	const renderPrintLabelFlow = () => {
		return (
			<Button onClick={ props.labelActions.openPrintingFlow } >
				{ __( 'Create label' ) }
			</Button>
		);
	};

	const renderSuccessNotice = () => {
		return <span>{ __( 'The shipping label has been successfully purchased.' ) }</span>
	};

	return (
		<p className="wcc-metabox-button-container">
			<GlobalNotices id="notices" notices={ notices.list } />
			<PrintLabelDialog
				{ ...( props.shippingLabel ) }
				{ ...props }
			/>
			{ props.shippingLabel.success ? renderSuccessNotice() : renderPrintLabelFlow() }
		</p>
	);
};

ShippingLabelRootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	return {
		shippingLabel: state.shippingLabel,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		labelActions: bindActionCreators( ShippingLabelActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( ShippingLabelRootView );
