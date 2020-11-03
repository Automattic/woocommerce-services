/** @format */
/* eslint-disable */

/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
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
import * as api from 'woocommerce/woocommerce-services/api';
import { getSelectedSiteId } from 'state/ui/selectors';
import { forEach } from 'lodash';

const FormFieldSet = ( props ) => {
    return (
        <div className="card carrier-accounts__settings-account-number is-compact">
            <fieldset className="form-fieldset">
                <label htmlFor={props.labelKey} className="form-label">{props.labelName}</label>
                <input type={props.inputType} id={props.labelKey} name={props.labelKey} className="form-text-input" value="" />
            </fieldset>
        </div>
    );
};

export const DynamicCarrierAccountSettingsForm = ( props ) => {
	const {
        carrierType,
        carrierName,
        registrationFields,
		siteId,
    } = props;

    const listOfFormFieldSet = [];

    console.log("re-render=>>>", props);
    if (props.registrationFields) {
        const listOfFields = Object.keys(props.registrationFields);
        listOfFields.forEach( fieldKey => {
            const inputType = props.registrationFields[fieldKey].visibility === 'visible' ? 'text' : 'password';
            listOfFormFieldSet.push(
                <FormFieldSet
                    labelName={props.registrationFields[fieldKey].label}
                    labelKey={fieldKey}
                    inputType={inputType}
                />
            );
        });
    }

    return (
		<div className="carrier-accounts__settings-form">
            <div className="card is-compact">
                <h4 className="carrier-accounts__settings-subheader">General Information</h4>
                <p className="carrier-accounts__settings-subheader-description">This is the account number and address from your UPS profile</p>
            </div>

            {listOfFormFieldSet}
		</div>
	);
};

const mapStateToProps = ( state ) => {
	return {
		siteId: getSelectedSiteId( state ),
    };
};

const mapDispatchToProps = ( dispatch, {siteId} ) => ( {
} );

DynamicCarrierAccountSettingsForm.propTypes = {
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettingsForm ) );

/* eslint-enable */
