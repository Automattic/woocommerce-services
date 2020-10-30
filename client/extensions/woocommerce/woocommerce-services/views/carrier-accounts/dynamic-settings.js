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
	setVisibilityCancelConnectionDialog,
	submitCarrierSettings,
	updateCarrierSettings,
	toggleShowUPSInvoiceFields,
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';
import { decodeEntities } from 'lib/formatting';

export const DynamicCarrierAccountSettings = ( props ) => {
	const {
		carrier,
		countryNames,
		fieldErrors,
		isConnectionSuccess,
		isFormValid,
		isSaving,
		showCancelConnectionDialog,
		showUPSInvoiceFields,
		siteId,
		stateNames,
		translate,
		values,
	} = props;

    return (
		<div>
            Hello world
		</div>
	);
};

const mapStateToProps = ( state ) => {
	return {
    };
};

DynamicCarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
};

export default connect( mapStateToProps )( localize( DynamicCarrierAccountSettings ) );

/* eslint-enable */