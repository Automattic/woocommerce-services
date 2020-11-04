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

const CheckboxFormFieldSet = ( props ) => {
    const changeHandler = () => {
    };

    return (
        <>
            <Checkbox
                id={ props.labelKey }
                onChange={changeHandler}
                checked={false}
            />
            <span>{props.labelName}</span>
        </>
    );
};


export const DynamicCarrierAccountSettingsForm = ( props ) => {
	const {
        carrierType,
        carrierName,
        registrationFields,
        siteId,
        translate,
    } = props;

    const listOfFormFieldSet = [];

    const getInputComponent = (props, fieldKey) => {
        const visibility = props.registrationFields[fieldKey].visibility;
        const labelName = props.registrationFields[fieldKey].label;
        const labelKey = fieldKey;

        let formComponent;

        switch (visibility) {
            case 'password':
                formComponent = <TextField
                    id={ labelKey }
                    title={ translate( labelName ) }
                    value=""
                />;
                break;
            case 'select':
            case 'checkbox':
                formComponent = <CheckboxFormFieldSet
                    labelKey={ labelKey }
                    labelName={ translate( labelName ) }
                />

                break;
            case 'visible':
            case 'invisible':
            case 'masked':
            case 'readonly':
            default:
                formComponent = <TextField
                    id={ labelKey }
                    title={ translate( labelName ) }
                    value=""
                />
                break;
        }
        return formComponent;
    };

    const submitCarrierSettingsHandler = () => {
        //TODO: Call register end point
    }

    const showCancelDialogHandler = () => {
        //TODO: when clicked cancel
    }

    console.log("re-render=>>>", props);
    if (props.registrationFields) {
        const listOfFields = Object.keys(props.registrationFields);
        listOfFields.forEach( fieldKey => {
            const inputComponent = getInputComponent(props, fieldKey);
            listOfFormFieldSet.push(inputComponent);
        });
    }

    return (
        <div className="carrier-accounts__settings-container">
			<div className="carrier-accounts__settings">
				<div className="carrier-accounts__settings-info">
					<h4 className="carrier-accounts__settings-subheader-above-description">
						{ translate( 'Connect your %(carrierName)s account', {
                            args: {
                                carrierName: props.carrierName
                            }
                        } ) }
					</h4>
					<p className="carrier-accounts__settings-subheader-description">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam et dolor quam.
					</p>
				</div>
				<div className="carrier-accounts__settings-form">
                    <CompactCard>
                        <h4 className="carrier-accounts__settings-subheader">General Information</h4>
                        <p className="carrier-accounts__settings-subheader-description">
                            { translate( 'This is the account number and address from your %(carrierName)s profile', {
                            args: {
                                carrierName: props.carrierName
                            }
                        } ) }
                        </p>
                    </CompactCard>

                    <CompactCard>
                        {listOfFormFieldSet}
                    </CompactCard>

                    <CompactCard className="carrier-accounts__settings-actions">
						<Button
							compact
							primary
							onClick={ submitCarrierSettingsHandler }>
							{ translate( 'Connect' ) }
						</Button>
						<Button compact onClick={ showCancelDialogHandler }>
							{ translate( 'Cancel' ) }
						</Button>
					</CompactCard>
                </div>
            </div>
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
