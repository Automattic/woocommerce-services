/** @format */
/* eslint-disable */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CompactCard from 'components/card/compact';
import Dialog from 'components/dialog';
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import {
	getDestinationCountryNames,
	getStateNames,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	getCarrierAccountsState,
	getFormErrors,
	getFormValidState,
} from 'woocommerce/woocommerce-services/state/carrier-accounts/selectors';
import {
	getCarrierRegistrationFields
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';
import { decodeEntities } from 'lib/formatting';

export const DynamicCarrierAccountSettings = ( props ) => {
	const {
		carrier,
		siteId,
	} = props;

    return (
		<div>
            Hello world
            <Button
				onClick={ props.getCarrierRegistrationFields }
				primary
			>
            Click to retrieve registration fields
            </Button>
		</div>
	);
};

const mapStateToProps = ( state ) => {
	return {
    };
};

const mapDispatchToProps = ( dispatch, {siteId} ) => ( {
	getCarrierRegistrationFields: () => dispatch( getCarrierRegistrationFields( siteId ) ),
} );

DynamicCarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettings ) );

/* eslint-enable */