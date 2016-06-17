import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';
import PrintLabelDialog from 'components/shipping-label';
import * as ShippingLabelActions from 'state/form/shipping-label/actions';

const RootView = ( props ) => {
	console.log(props.form);
	return (
		<p className="wcc-metabox-button-container">
			<PrintLabelDialog
				{ ...( props.form.shippingLabel ) }
				{ ...props }
			/>
			<Button
				onClick={ props.labelActions.openPrintingFlow }
				>
				{ __( 'Create label' ) }
			</Button>
		</p>
	);
};

RootView.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

function mapStateToProps( state ) {
	return {
		form: state.form,
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		labelActions: bindActionCreators( ShippingLabelActions, dispatch )
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( RootView );
