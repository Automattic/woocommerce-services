/**
 * External dependencies
 */
import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
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
import { errorNotice as errorNoticeAction, successNotice as successNoticeAction } from 'state/notices/actions';

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
		errorNotice,
		successNotice,
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
				const result = await api.post( siteId, api.url.shippingCarrier(), {
					...formValues,
					type: carrierType,
				} );

				if( ! result.success ) {
					throw new Error();
				}

				successNotice( translate( 'Your carrier account was connected successfully.' ) )

				return;

				const url = new URL(window.location.href)
				url.searchParams.delete('carrier')
				window.onbeforeunload = null
				window.location.href = url.href
			} catch (error) {
				errorNotice(translate(
					'There was an error connecting to your %(carrierName)s account. Please check that all of the information entered matches your %(carrierName)s account and try to connect again.',
					{
						args: { carrierName },
					}
				));
			}

			setIsSaving(false);
		}

		submit();
	}, [isSaving, setIsSaving, siteId, carrierType, carrierName, formValues, errorNotice, translate] );

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
					<h4 className="carrier-accounts__settings-subheader">{ translate ( 'General Information' ) }</h4>
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

const mapDispatchToProps = {
	errorNotice: errorNoticeAction,
	successNotice: successNoticeAction,
};

DynamicCarrierAccountSettingsForm.propTypes = {
	errorNotice: PropTypes.func,
	successNotice: PropTypes.func,
	carrierType: PropTypes.string,
	carrierName: PropTypes.string,
	registrationFields: PropTypes.object,
	siteId: PropTypes.number,
	translate: PropTypes.func,
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettingsForm ) );
