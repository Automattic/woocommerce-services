/**
 * External dependencies
 */
import React, { useState, useCallback, useMemo } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { compose } from 'redux'
import { Card } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { localize } from 'i18n-calypso'
import Button from 'components/button'
import ButtonModal from 'components/button-modal';
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown'
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox'
import TextField from 'woocommerce/woocommerce-services/components/text-field'
import {
	getDestinationCountryNames,
	getStateNames,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors'
import { getSelectedSiteId } from 'state/ui/selectors';
import { decodeEntities } from 'lib/formatting'
import { errorNotice as errorNoticeAction, successNotice as successNoticeAction } from 'state/notices/actions'
import * as api from 'woocommerce/woocommerce-services/api'

const emailRegex = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
const USPostalCodeRegex = /^\d{5}$/;
const UPSAccountNumberRegex = /^[a-zA-Z0-9]{6}$/;
const UPSInvoiceNumberRegex = /^(\d{9}|\d{13})$/;
const phoneRegex = /^\d{10}$/;
const dateRegex = /^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/;
const urlRegex = /^(?:(?:(?:https?|ftp):)?\/\/)*(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z0-9\u00a1-\uffff][a-z0-9\u00a1-\uffff_-]{0,62})?[a-z0-9\u00a1-\uffff]\.)+(?:[a-z\u00a1-\uffff]{2,}\.?))(?::\d{2,5})?(?:[/?#]\S*)?$/i;

const getFieldsErrors = (values, isInvoiceDetailsChecked, translate) => {
	const fieldsErrors = {};

	const requiredFields = [
		'account_number',
		'name',
		'street1',
		'city',
		'state',
		'country',
		'postal_code',
		'phone',
		'email',
		'title',
		'website',
		isInvoiceDetailsChecked && 'invoice_number',
		isInvoiceDetailsChecked && 'invoice_date',
	].filter(Boolean);

	for ( const field of requiredFields ) {
		const value = values[ field ];
		if ( ! value || ( 'string' === typeof value && '' === value.trim() ) ) {
			fieldsErrors[ field ] = translate( 'This field is required' );
		}
	}

	if ( values.account_number && ! values.account_number.match( UPSAccountNumberRegex ) ) {
		fieldsErrors.account_number = translate( 'The UPS account number needs to be 6 letters and digits in length' );
	}

	if ( values.phone && ! values.phone.match( phoneRegex ) ) {
		fieldsErrors.phone = translate( 'The phone number needs to be 10 digits in length' );
	}

	if ( values.country === 'US' && values.postal_code && ! values.postal_code.match( USPostalCodeRegex ) ) {
		fieldsErrors.postal_code = translate( 'The ZIP/Postal code needs to be 5 digits in length' );
	}

	if ( values.email && ! values.email.match( emailRegex ) ) {
		fieldsErrors.email = translate( 'The email format is not valid' );
	}

	if ( values.website && ! values.website.match( urlRegex ) ) {
		fieldsErrors.website = translate( 'The company website format is not valid' );
	}

	if ( isInvoiceDetailsChecked && values.invoice_number && ! values.invoice_number.match( UPSInvoiceNumberRegex ) ) {
		fieldsErrors.invoice_number = translate( 'The invoice number needs to be 9 or 13 digits in length' );
	}

	if ( isInvoiceDetailsChecked && values.invoice_date && ! values.invoice_date.match( dateRegex ) ) {
		fieldsErrors.invoice_date = translate( 'The date must be a valid date in the format YYYY-MM-DD' );
	}

	return fieldsErrors;
}

const CancelDialog = localize(({ isVisible, onCancel, onConfirm, translate }) => {
	const buttons = useMemo(() => (
		[
			<Button compact onClick={onCancel} key="cancel">
				{translate('Cancel')}
			</Button>,
			<Button compact primary scary onClick={onConfirm} key="ok">
				{translate('Ok')}
			</Button>,
		]
	), [onCancel, onConfirm, translate]);

	return (
		<ButtonModal
			isVisible={ isVisible }
			additionalClassNames="carrier-accounts__settings-cancel-dialog"
			onClose={ onCancel }
			buttons={ buttons }
			title={ translate('Cancel connection') }
		>
			<p className="carrier-accounts__settings-cancel-dialog-description">
				{translate('This action will delete any information entered on the form.')}
			</p>
		</ButtonModal>
	)
});

const StateInput = compose(connect((state, ownProps) => {
	return {
		stateNames: getStateNames( state, ownProps.countryValue ),
	}
}), localize)(({onUpdate, error, stateNames, translate, value}) => {
	const statesValuesMap = useMemo(() => {
		if(!stateNames) return stateNames;

		return { '': translate('Select one…'), ...stateNames };
	}, [stateNames, translate]);

	if(statesValuesMap) {
		return (
			<Dropdown
				id="state"
				title={translate('State')}
				value={value}
				valuesMap={statesValuesMap}
				updateValue={onUpdate}
				error={error}
			/>
		);
	}

	return (
		<TextField
			id="state"
			title={translate('State')}
			updateValue={onUpdate}
			error={error}
		/>
	);
})

const UpsSettingsForm = ({ translate, errorNotice, successNotice, countryNames, siteId }) => {
	const [isSaving, setIsSaving] = useState(false);
	const [isInvoiceDetailsChecked, setIsInvoiceDetailsChecked] = useState(false);
	const [isCancelDialogVisible, setIsCancelDialogVisible] = useState(false);
	const [formValues, setFormValues] = useState({country: 'US'});

	const fieldsErrors = getFieldsErrors(formValues, isInvoiceDetailsChecked, translate);

	const getValue = (fieldName) => formValues[fieldName] ? decodeEntities(formValues[fieldName]) : ''

	const handleFormFieldUpdate = useCallback((value, event) => {
		// using a separate `const` for `id` ensures that on async update of `setFormValues` the synthetic event is not accessed
		const { id } = event.currentTarget;

		setFormValues(values => ({
			...values,
			[id]: value
		}));
	}, [setFormValues]);

	const handleSubmit = useCallback(() => {
		const submit = async () => {
			setIsSaving(true);
			try {
				const requestBody = {
					...formValues,
					type: 'UpsAccount',
				};

				if( isInvoiceDetailsChecked ) {
					delete formValues.invoice_number;
					delete formValues.invoice_date;
					delete formValues.invoice_amount;
					delete formValues.invoice_currency;
					delete formValues.invoice_control_id;
				}

				const result = await api.post( siteId, api.url.shippingCarrier(), requestBody );

				if( ! result.success ) {
					throw new Error();
				}

				successNotice( translate( 'Your carrier account was connected successfully.' ) )

				const url = new URL(window.location.href)
				url.searchParams.delete('carrier')
				window.onbeforeunload = null
				window.location.href = url.href
			} catch(err) {
				setIsSaving(false);
				errorNotice(translate(
					'There was an error connecting to your %(carrierName)s account. Please check that all of the information entered matches your %(carrierName)s account and try to connect again.',
					{ args: { carrierName: 'UPS' } }
				));
			}
		};

		submit();
	}, [setIsSaving, errorNotice, successNotice, translate, formValues, isInvoiceDetailsChecked]);

	const handleToggleInvoiceDetailsFieldsVisibility = useCallback(() => {
		setIsInvoiceDetailsChecked(val => !val)
	}, [setIsInvoiceDetailsChecked])

	const handleCancelDialogCancel = useCallback(() => {setIsCancelDialogVisible(false)}, [setIsCancelDialogVisible]);
	const handleCancelDialogConfirm = useCallback(() => {
		const url = new URL(window.location.href)
		url.searchParams.delete('carrier')
		window.onbeforeunload = null
		window.location.href = url.href
	}, []);
	const handleCancelClick = useCallback(() => {setIsCancelDialogVisible(true)}, [setIsCancelDialogVisible])

	return (
		<div className="carrier-accounts__settings-container">
			<div className="carrier-accounts__settings">
				<div className="carrier-accounts__settings-info">
					<h4 className="carrier-accounts__settings-subheader-above-description">
						{translate('Connect your UPS account')}
					</h4>
					<p className="carrier-accounts__settings-subheader-description">
						{translate(
							'Set up your own UPS carrier account to compare rates and print labels from multiple carriers in WooCommerce Shipping. Learn more about adding {{a}}carrier accounts{{/a}}.',
							{
								components: {
									a: <a href="https://docs.woocommerce.com/document/productid-type-permalinks/using-your-own-ups-account-in-woocommerce-shipping/"/>
								}
							}
						)}
					</p>
					<p className="carrier-accounts__settings-subheader-description">
						{translate(
							'If you need a UPS account number, go to {{a}}UPS.com{{/a}} to create a new account.',
							{ components: { a: <a href="https://ups.com/"/> } }
						)}
					</p>
				</div>
				<div className="carrier-accounts__settings-form">
					<Card className={ classNames( "card", "is-compact" ) } >
						<h4 className="carrier-accounts__settings-subheader">{translate('General information')}</h4>
						<p className="carrier-accounts__settings-subheader-description">
							{translate('This is the account number and address from your UPS profile')}
						</p>
					</Card>
					<Card className={ classNames( "carrier-accounts__settings-account-number", "card", "is-compact" ) } >
						<TextField
							id="account_number"
							title={translate('Account number')}
							updateValue={handleFormFieldUpdate}
							error={typeof formValues.account_number === 'string' ? fieldsErrors.account_number : undefined}
						/>
					</Card>
					<Card className={ classNames( "carrier-accounts__settings-address", "card", "is-compact" ) } >
						<TextField
							id="name"
							title={translate('Name')}
							updateValue={handleFormFieldUpdate}
							error={typeof formValues.name === 'string' ? fieldsErrors.name : undefined}
						/>
						<TextField
							id="street1"
							title={translate('Address')}
							updateValue={handleFormFieldUpdate}
							error={typeof formValues.street1 === 'string' ? fieldsErrors.street1 : undefined}
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="street2"
								title={translate('Address 2 (optional)')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.street2 === 'string' ? fieldsErrors.street2 : undefined}
							/>
							<TextField
								id="city"
								title={translate('City')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.city === 'string' ? fieldsErrors.city : undefined}
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							<StateInput
								countryValue={formValues.country}
								value={getValue('state')}
								onUpdate={handleFormFieldUpdate}
								error={typeof formValues.state === 'string' ? fieldsErrors.state : undefined}
							/>

							<Dropdown
								id="country"
								title={translate('Country')}
								value={getValue('country')}
								valuesMap={countryNames}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.country === 'string' ? fieldsErrors.country : undefined}
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="postal_code"
								title={translate('ZIP/Postal code')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.postal_code === 'string' ? fieldsErrors.postal_code : undefined}
							/>
							<TextField
								id="phone"
								title={translate('Phone')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.phone === 'string' ? fieldsErrors.phone : undefined}
							/>
						</div>
						<TextField
							id="email"
							title={translate('Email')}
							updateValue={handleFormFieldUpdate}
							error={typeof formValues.email === 'string' ? fieldsErrors.email : undefined}
						/>
					</Card>
					<Card className={ classNames( "carrier-accounts__settings-company-info", "card", "is-compact" ) } >
						<div className="carrier-accounts__settings-header">
							<h4 className="carrier-accounts__settings-subheader">
								{translate('Company information')}
							</h4>
							<p className="carrier-accounts__settings-subheader-description">
								{translate('This is the company info you used to create your UPS account')}
							</p>
						</div>
						<TextField
							id="company"
							title={translate('Company name')}
							updateValue={handleFormFieldUpdate}
							error={typeof formValues.company === 'string' ? fieldsErrors.company : undefined}
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="title"
								title={translate('Job title')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.title === 'string' ? fieldsErrors.title : undefined}
							/>
							<TextField
								id="website"
								title={translate('Company website')}
								updateValue={handleFormFieldUpdate}
								error={typeof formValues.website === 'string' ? fieldsErrors.website : undefined}
							/>
						</div>
					</Card>
					<Card className={ classNames( "carrier-accounts__settings-ups-info", "card", "is-compact" ) } >
						<div className="carrier-accounts__settings-header">
							<h4 className="carrier-accounts__settings-subheader">
								{translate('UPS account information')}
							</h4>
							<Checkbox
								id="enable_ups_invoice_fields"
								checked={isInvoiceDetailsChecked}
								onChange={handleToggleInvoiceDetailsFieldsVisibility}
							/>
							<span>
								{translate('I have been issued an invoice from UPS within the past 90 days')}
							</span>
						</div>
						{isInvoiceDetailsChecked && (
							<div className="carrier-accounts__settings-ups-invoice">
								<div className="carrier-accounts__settings-two-columns">
									<TextField
										id="invoice_number"
										title={translate('UPS invoice number')}
										updateValue={handleFormFieldUpdate}
										error={typeof formValues.invoice_number === 'string' ? fieldsErrors.invoice_number : undefined}
									/>
									<TextField
										id="invoice_date"
										title={translate('UPS invoice date')}
										updateValue={handleFormFieldUpdate}
										error={typeof formValues.invoice_date === 'string' ? fieldsErrors.invoice_date : undefined}
										placeholder={'YYYY-MM-DD'}
									/>
								</div>
								<div className="carrier-accounts__settings-two-columns">
									<TextField
										id="invoice_amount"
										title={translate('UPS invoice amount')}
										updateValue={handleFormFieldUpdate}
										error={typeof formValues.invoice_amount === 'string' ? fieldsErrors.invoice_amount : undefined}
									/>
									<TextField
										id="invoice_currency"
										title={translate('UPS invoice currency')}
										updateValue={handleFormFieldUpdate}
										error={typeof formValues.invoice_currency === 'string' ? fieldsErrors.invoice_currency : undefined}
									/>
								</div>
								<TextField
									id="invoice_control_id"
									title={translate('UPS invoice control id')}
									updateValue={handleFormFieldUpdate}
									error={typeof formValues.invoice_control_id === 'string' ? fieldsErrors.invoice_control_id : undefined}
								/>
							</div>
						)}
					</Card>
					<Card className={ classNames( "carrier-accounts__settings-actions", "card", "is-compact" ) } >
						<Button
							compact
							primary
							onClick={handleSubmit}
							disabled={Object.keys(fieldsErrors).length > 0 || isSaving}
							busy={isSaving}
						>
							{translate('Connect')}
						</Button>
						<Button compact onClick={handleCancelClick}>
							{translate('Cancel')}
						</Button>
					</Card>
				</div>
				<CancelDialog isVisible={isCancelDialogVisible} onConfirm={handleCancelDialogConfirm} onCancel={handleCancelDialogCancel}/>
			</div>
		</div>
	);
}

UpsSettingsForm.propTypes = {
	siteId: PropTypes.number,
	countryNames: PropTypes.object,
	translate: PropTypes.func,
	errorNotice: PropTypes.func,
	successNotice: PropTypes.func,
}

const mapStateToProps = (state) => ( {
	countryNames: getDestinationCountryNames(state),
	siteId: getSelectedSiteId( state ),
});

const mapDispatchToProps = {
	errorNotice: errorNoticeAction,
	successNotice: successNoticeAction,
}

export default compose(connect(mapStateToProps, mapDispatchToProps), localize)(UpsSettingsForm);
