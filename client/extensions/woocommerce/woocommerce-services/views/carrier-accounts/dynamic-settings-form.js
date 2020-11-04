/** @format */

/**
 * External dependencies
 */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CompactCard from 'components/card/compact';
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import { getSelectedSiteId } from 'state/ui/selectors';
import * as api from 'woocommerce/woocommerce-services/api';
import { errorNotice, successNotice } from 'state/notices/actions';

const CheckboxFormFieldSet = ( props ) => {
    return (
        <>
            <Checkbox
                id={ props.fieldKey }
                onChange={props.onChange}
                checked={props.checked||false}
            />
            <label htmlFor={ props.fieldKey }><span>{props.labelName}</span></label>
        </>
    );
};

export const DynamicCarrierAccountSettingsForm = ( props ) => {
	const {
        translate,
    } = props;

    const listOfFormFieldSet = [];

    const [formFields, setFormFields] = useState({});
    const [isSaving, setIsSaving] = useState(false);

    const updateValue = ( fieldKey ) => ( newValue ) => {
        const formFieldsCopy = {...formFields};
        formFieldsCopy[fieldKey] = newValue;
        setFormFields(formFieldsCopy);
    }

    const toggleCheckbox = ( fieldKey ) => () => {
        const formFieldsCopy = {...formFields};
        formFieldsCopy[fieldKey] = !formFieldsCopy[fieldKey];
        setFormFields(formFieldsCopy);
    };

    const getInputComponent = (fieldKey) => {
        const visibility = props.registrationFields[fieldKey].visibility;
        const labelName = props.registrationFields[fieldKey].label;

        let formComponent;

        switch (visibility) {
            case 'select':
            case 'checkbox':
                formComponent = <CheckboxFormFieldSet
                    key={ fieldKey }
                    fieldKey={ fieldKey }
                    labelName={ labelName }
                    onChange={toggleCheckbox(fieldKey)}
                    checked={formFields[fieldKey]}
                />

                break;
            case 'invisible':
            case 'masked':
            case 'readonly':
                // TODO: We will need to handle the above invisible/readonly fields separately. For now, use default.
            case 'password':
                // TODO: We will need to create a PasswordField component, for now, use default.
            case 'visible':
            default:
                formComponent = <TextField
                    id={ fieldKey }
                    key={ fieldKey }
                    title={ labelName }
                    updateValue={ updateValue( fieldKey ) }
                    value={formFields[fieldKey]||""}
                />
                break;
        }
        return formComponent;
    };

    const submitCarrierSettingsHandler = async () => {
        setIsSaving(true);
        try {
            await api.post( props.siteId, api.url.shippingCarrier(), {
                type: props.carrierType,
                settings: formFields
            } );
        } catch (error) {
            props.noticeActions.errorNotice(`Failed to register the carrier. ${ error }`);
        }
        setIsSaving(false);
	};

    const showCancelDialogHandler = () => {
        history.back();
    }

    if (props.registrationFields) {
        const listOfFields = Object.keys(props.registrationFields);
        listOfFields.forEach( fieldKey => {
            const inputComponent = getInputComponent(fieldKey);
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
                            onClick={ submitCarrierSettingsHandler }
                            disabled={ isSaving }
                        >
							{ translate( 'Connect' ) }
						</Button>
                        <Button
                            compact
                            onClick={ showCancelDialogHandler }
                        >
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

const mapDispatchToProps = ( dispatch ) => ({
	noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
});

CheckboxFormFieldSet.propTypes = {
    fieldKey: PropTypes.string,
    labelName: PropTypes.string,
    onChange: PropTypes.func,
    checked: PropTypes.bool,
};

DynamicCarrierAccountSettingsForm.propTypes = {
    carrierType: PropTypes.string,
    carrierName: PropTypes.string,
    registrationFields: PropTypes.object,
    siteId: PropTypes.number,
    translate: PropTypes.func,
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettingsForm ) );
