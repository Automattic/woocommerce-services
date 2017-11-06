/**
 * External dependencies
 */
import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
// from calypso
import Button from 'components/button';
import Packages from 'woocommerce/woocommerce-services/views/packages';
import { submit } from 'woocommerce/woocommerce-services/state/packages/actions';

const PackagesWrapper = ( props ) => {
	const { translate } = props;

	const onSave = () => props.submit( 1, () => console.log( 'ok' ), () => console.log( 'not ok' ) );

	return (
		<div>
			<Packages />
			<Button primary onClick={ onSave }>{ translate( 'Save' ) }</Button>
		</div>
	);
};

export default connect(
	( state ) => state,
	( dispatch ) => bindActionCreators( { submit }, dispatch )
)( localize( PackagesWrapper ) );
