// eslint-disable-next-line wpcalypso/import-docblock
import React, { useState, useCallback, useMemo } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { compose } from 'redux'
import { localize } from 'i18n-calypso'
import Gridicon from 'gridicons'
import Button from 'components/button'
import CompactCard from 'components/card/compact'
import Dialog from 'components/dialog'
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown'
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox'
import TextField from 'woocommerce/woocommerce-services/components/text-field'
import {
	getDestinationCountryNames,
	getStateNames,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors'
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors'
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

const getFieldsErrors = (values, translate) => {
	const fieldErrors = {};

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
	]

	for ( const field of requiredFields ) {
		const value = values[ field ];
		if ( ! value || ( 'string' === typeof value && '' === value.trim() ) ) {
			fieldErrors[ field ] = translate( 'This field is required' );
		}
	}

	if ( values.account_number && ! values.account_number.match( UPSAccountNumberRegex ) ) {
		fieldErrors.account_number = translate( 'The UPS account number needs to be 6 letters and digits in length' );
	}

	if ( values.phone && ! values.phone.match( phoneRegex ) ) {
		fieldErrors.phone = translate( 'The phone number needs to be 10 digits in length' );
	}

	if ( values.country === 'US' && values.postal_code && ! values.postal_code.match( USPostalCodeRegex ) ) {
		fieldErrors.postal_code = translate( 'The ZIP/Postal code needs to be 5 digits in length' );
	}

	if ( values.email && ! values.email.match( emailRegex ) ) {
		fieldErrors.email = translate( 'The email format is not valid' );
	}

	if ( values.website && ! values.website.match( urlRegex ) ) {
		fieldErrors.website = translate( 'The company website format is not valid' );
	}

	if ( values.invoice_number && ! values.invoice_number.match( UPSInvoiceNumberRegex ) ) {
		fieldErrors.invoice_number = translate( 'The invoice number needs to be 9 or 13 digits in length' );
	}

	if ( values.invoice_date && ! values.invoice_date.match( dateRegex ) ) {
		fieldErrors.invoice_date = translate( 'The date must be a valid date in the format YYYY-MM-DD' );
	}

	return fieldErrors;
}

const CancelDialog = localize(({ isVisible, onCancel, translate }) => {
	const buttons = useMemo(() => (
		[
			<Button compact onClick={onCancel} key="cancel">
				{translate('Cancel')}
			</Button>,
			<Button compact primary scary onClick={() => history.back()} key="ok">
				{translate('Ok')}
			</Button>,
		]
	), [onCancel])

	return (
		<Dialog
			isVisible={isVisible}
			additionalClassNames="carrier-accounts__settings-cancel-dialog"
			onClose={onCancel}
			buttons={buttons}
		>
			<div className="carrier-accounts__settings-cancel-dialog-header">
				<h2 className="carrier-accounts__settings-cancel-dialog-title">
					{translate('Cancel connection')}
				</h2>
				<button
					className="carrier-accounts__settings-cancel-dialog-close-button"
					onClick={onCancel}
				>
					<Gridicon icon="cross"/>
				</button>
			</div>
			<p className="carrier-accounts__settings-cancel-dialog-description">
				{translate('This action will delete any information entered on the form.')}
			</p>
		</Dialog>
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
	)
})

const UpsSettingsForm = ({ translate, errorNotice, successNotice, countryNames, siteId }) => {
	const [isSaving, setIsSaving] = useState(false);
	const [isInvoiceDetailsChecked, setIsInvoiceDetailsChecked] = useState(false);
	const [isCancelDialogVisible, setIsCancelDialogVisible] = useState(false);
	const [formValues, setFormValues] = useState({country: 'US'});

	const fieldErrors = getFieldsErrors(formValues, translate);
	const isFormValid = Object.keys(fieldErrors).length === 0 && (isInvoiceDetailsChecked ? false : true);

	const getValue = (fieldName) => formValues[fieldName] ? decodeEntities(formValues[fieldName]) : ''

	const handleFormFieldValueUpdate = useCallback((value, event) => {
		// using a separate `const` ensures that on async update of `setFormValues` the synthetic event is not accessed
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
				const result = await api.post( siteId, api.url.shippingCarrier(), { ...formValues, type: 'UpsAccount' } );

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
					{
						args: { carrierName: 'UPS' },
					}
				));
			}
		};

		submit();
	}, [setIsSaving, errorNotice, successNotice, translate, formValues]);

	const handleToggleInvoiceDetailsFieldsVisibility = useCallback(() => {
		if(isInvoiceDetailsChecked) {
			delete formValues.invoice_number;
			delete formValues.invoice_date;
			delete formValues.invoice_amount;
			delete formValues.invoice_currency;
			delete formValues.invoice_control_id;
		}
		setIsInvoiceDetailsChecked(val => !val)
	}, [isInvoiceDetailsChecked, setIsInvoiceDetailsChecked])

	const handleCancelDialogCancel = useCallback(() => {setIsCancelDialogVisible(false)}, [setIsCancelDialogVisible])

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
									a: <a
										href="https://docs.woocommerce.com/document/using-your-own-ups-account-in-woocommerce-shipping/"/>
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
					<CompactCard>
						<h4 className="carrier-accounts__settings-subheader">{translate('General information')}</h4>
						<p className="carrier-accounts__settings-subheader-description">
							{translate('This is the account number and address from your UPS profile')}
						</p>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-account-number">
						<TextField
							id="account_number"
							title={translate('Account number')}
							updateValue={handleFormFieldValueUpdate}
							error={typeof formValues.account_number === 'string' ? fieldErrors.account_number : undefined}
						/>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-address">
						<TextField
							id="name"
							title={translate('Name')}
							updateValue={handleFormFieldValueUpdate}
							error={typeof formValues.name === 'string' ? fieldErrors.name : undefined}
						/>
						<TextField
							id="street1"
							title={translate('Address')}
							updateValue={handleFormFieldValueUpdate}
							error={typeof formValues.street1 === 'string' ? fieldErrors.street1 : undefined}
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="street2"
								title={translate('Address 2 (optional)')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.street2 === 'string' ? fieldErrors.street2 : undefined}
							/>
							<TextField
								id="city"
								title={translate('City')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.city === 'string' ? fieldErrors.city : undefined}
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							<StateInput
								countryValue={formValues.country}
								value={getValue('state')}
								onUpdate={handleFormFieldValueUpdate}
								error={typeof formValues.state === 'string' ? fieldErrors.state : undefined}
							/>

							<Dropdown
								id="country"
								title={translate('Country')}
								value={getValue('country')}
								valuesMap={countryNames}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.country === 'string' ? fieldErrors.country : undefined}
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="postal_code"
								title={translate('ZIP/Postal code')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.postal_code === 'string' ? fieldErrors.postal_code : undefined}
							/>
							<TextField
								id="phone"
								title={translate('Phone')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.phone === 'string' ? fieldErrors.phone : undefined}
							/>
						</div>
						<TextField
							id="email"
							title={translate('Email')}
							updateValue={handleFormFieldValueUpdate}
							error={typeof formValues.email === 'string' ? fieldErrors.email : undefined}
						/>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-company-info">
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
							updateValue={handleFormFieldValueUpdate}
							error={typeof formValues.company === 'string' ? fieldErrors.company : undefined}
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id="title"
								title={translate('Job title')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.title === 'string' ? fieldErrors.title : undefined}
							/>
							<TextField
								id="website"
								title={translate('Company website')}
								updateValue={handleFormFieldValueUpdate}
								error={typeof formValues.website === 'string' ? fieldErrors.website : undefined}
							/>
						</div>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-ups-info">
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
										updateValue={handleFormFieldValueUpdate}
										error={typeof formValues.invoice_number === 'string' ? fieldErrors.invoice_number : undefined}
									/>
									<TextField
										id="invoice_date"
										title={translate('UPS invoice date')}
										updateValue={handleFormFieldValueUpdate}
										error={typeof formValues.invoice_date === 'string' ? fieldErrors.invoice_date : undefined}
										placeholder={'YYYY-MM-DD'}
									/>
								</div>
								<div className="carrier-accounts__settings-two-columns">
									<TextField
										id="invoice_amount"
										title={translate('UPS invoice amount')}
										updateValue={handleFormFieldValueUpdate}
										error={typeof formValues.invoice_amount === 'string' ? fieldErrors.invoice_amount : undefined}
									/>
									<TextField
										id="invoice_currency"
										title={translate('UPS invoice currency')}
										updateValue={handleFormFieldValueUpdate}
										error={typeof formValues.invoice_currency === 'string' ? fieldErrors.invoice_currency : undefined}
									/>
								</div>
								<TextField
									id="invoice_control_id"
									title={translate('UPS invoice control id')}
									updateValue={handleFormFieldValueUpdate}
									error={typeof formValues.invoice_control_id === 'string' ? fieldErrors.invoice_control_id : undefined}
								/>
							</div>
						)}
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-actions">
						<Button
							compact
							primary
							onClick={handleSubmit}
							disabled={!isFormValid || isSaving}
							busy={isSaving}
						>
							{translate('Connect')}
						</Button>
						<Button compact onClick={() => {
							setIsCancelDialogVisible(true)
						}}>
							{translate('Cancel')}
						</Button>
					</CompactCard>
				</div>
				<CancelDialog isVisible={isCancelDialogVisible} onCancel={handleCancelDialogCancel}/>
			</div>
		</div>
	)
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
});

const mapDispatchToProps = {
	errorNotice: errorNoticeAction,
	successNotice: successNoticeAction,
}

export default compose(connect(mapStateToProps, mapDispatchToProps), localize)(UpsSettingsForm)
