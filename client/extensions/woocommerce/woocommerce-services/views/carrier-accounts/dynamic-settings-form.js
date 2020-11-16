/**
 * External dependencies
 */
import React, { useState, useCallback } from 'react';
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

const FormFieldFactory = ( { visibility, label, id, value, onChange } ) => {
	const handleToggle = useCallback(() => {
		onChange(id, !value);
	}, [id, value, onChange]);

	const handleTextChange = useCallback(( newValue ) => {
		onChange(id, newValue);
	}, [id, value, onChange]);

	switch (visibility) {
		case 'select':
		case 'checkbox':
			return (
				<>
					<Checkbox
						id={ id }
						onChange={ handleToggle }
						checked={ value || false }
					/>
					<label htmlFor={ id }><span>{ label }</span></label>
				</>
			);

		case 'invisible':
		case 'masked':
		case 'readonly':
		// TODO: We will need to handle the above invisible/readonly fields separately. For now, use default.
		case 'password':
		// TODO: We will need to create a PasswordField component, for now, use default.
		case 'visible':
		default:
			return (
				<TextField
					id={ id }
					title={ label }
					updateValue={ handleTextChange }
					value={ value || '' }
				/>
			);
	}
}

export const DynamicCarrierAccountSettingsForm = ( props ) => {
	const {
        translate,
		siteId,
		carrierType,
		noticeActions,
		carrierName,
    } = props;

    const [formValues, setFormValues] = useState({});
    const [isSaving, setIsSaving] = useState(false);

    const handleFormFieldChange = useCallback((id, newValue) => {
    	setFormValues((oldValues) => ({
    		...oldValues,
		    [id]: newValue,
	    }))
    }, [setFormValues]);

	const handleSubmit = useCallback( () => {
		const submit = async () => {
			setIsSaving(true);

			try {
				await api.post( siteId, api.url.shippingCarrier(), {
					type: carrierType,
					settings: formValues
				} );
			} catch (error) {
				noticeActions.errorNotice(`Failed to register the carrier. ${ error }`);
			}

			setIsSaving(false);
		}

		submit();
	}, [isSaving, setIsSaving, siteId, carrierType, formValues, noticeActions] );

    const handleCancel = useCallback(() => {
        history.back();
    }, []);

    return (
        <div className="carrier-accounts__settings-container">
			<div className="carrier-accounts__settings">
				<div className="carrier-accounts__settings-info">
					<h4 className="carrier-accounts__settings-subheader-above-description">
						{ translate( 'Connect your %(carrierName)s account', {
                            args: {
                                carrierName,
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
                                carrierName,
                            }
                        } ) }
                        </p>
                    </CompactCard>

                    <CompactCard>
                        {props.registrationFields && Object.entries(props.registrationFields).map( ( [key, field] ) => (
                        	<FormFieldFactory key={key} id={key} visibility={field.visibility} label={field.label} value={formValues[key]} onChange={handleFormFieldChange} />
                        ))}
                    </CompactCard>

                    <CompactCard className="carrier-accounts__settings-actions">
						<Button
							compact
							primary
                            onClick={ handleSubmit }
                            disabled={ isSaving }
                        >
							{ translate( 'Connect' ) }
						</Button>
                        <Button
                            compact
                            onClick={ handleCancel }
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

DynamicCarrierAccountSettingsForm.propTypes = {
    carrierType: PropTypes.string,
    carrierName: PropTypes.string,
    registrationFields: PropTypes.object,
    siteId: PropTypes.number,
    translate: PropTypes.func,
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettingsForm ) );