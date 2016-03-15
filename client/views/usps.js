import React from 'react';
import SectionHeader from 'components/section-header';
import FormToggle from 'components/forms/form-toggle';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormSelect from 'components/forms/form-select';
import FormButton from 'components/forms/form-button';
import FormRadio from 'components/forms/form-radio';
import FormCheckbox from 'components/forms/form-checkbox';
import CompactCard from 'components/card/compact';

export default React.createClass( {
	displayName: 'Settings',
	render: function() {
		return (
			<div>
				<SectionHeader label="USPS Shipping">
					<FormToggle id="enabled" name="enabled" checked={true}><FormLabel htmlFor="enabled" style={{float: 'left'}}>Enable</FormLabel></FormToggle>
				</SectionHeader>
				<CompactCard>
					<FormSectionHeading>Setup</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="method_title">Shipping method title</FormLabel>
						<FormTextInput id="method_title" name="method_title" placeholder="USPS" />
						<FormSettingExplanation>The customer will see this during checkout</FormSettingExplanation>
					</FormFieldset>
					<FormFieldset>
						<FormLabel htmlFor="usps_account">USPS Account</FormLabel>
						<FormTextInput id="usps_account" name="usps_account" placeholder="WOOUSPS2016" />
						<FormSettingExplanation>
							Use the account provided or <a href="#">sign up for your own</a>
						</FormSettingExplanation>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormSectionHeading>Rates</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="rate_types">USPS Rates</FormLabel>
						<FormSelect id="rate_types" value="some">
							<option value="all">Use all available USPS rates</option>
							<option value="some">Let me pick which rates to offer</option>
						</FormSelect>
						<FormButton type="button" isPrimary={ false }>Advanced Options</FormButton>
						<FormSettingExplanation>These rates are automatically sent from the U.S. Post Office</FormSettingExplanation>
					</FormFieldset>
					<FormFieldset>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail Express™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>First-Class Mail®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Standard Post™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Media Mail</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Library Mail</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail Express Flat Rate®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail Flat Rate®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail Express International™</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Priority Mail International®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>Global Express Guaranteed®</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>First Class Mail® International Letters</span>
						</FormLabel>
						<FormLabel>
							<FormCheckbox checked={true} />
							<span>International Postcards</span>
						</FormLabel>
					</FormFieldset>
					<FormFieldset>
						<FormLegend>Rate Pricing</FormLegend>
						<FormLabel>
							<FormRadio value="retail" checked={true} />
							<span>Retail</span>
						</FormLabel>
						<FormLabel>
							<FormRadio value="commercial" checked={false} />
							<span>Commercial</span>
						</FormLabel>
					</FormFieldset>
					<FormFieldset>
						<FormLegend>Show the customer</FormLegend>
						<FormLabel>
							<FormRadio value="all" checked={true} />
							<span>All available rates that apply and let them choose</span>
						</FormLabel>
						<FormLabel>
							<FormRadio value="cheapest" checked={false} />
							<span>Only give them the one, cheapest rate</span>
						</FormLabel>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormSectionHeading>Packages</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="packing_method">Packing method</FormLabel>
						<FormSelect id="packing_method" value="box_packing">
							<option value="box_packing">When cheaper, pack multiple items in a single package</option>
							<option value="per_item">Pack items individually</option>
							<option value="weight_based">Group regular items (less than 12 inches) and get a quote by weight</option>
						</FormSelect>
					</FormFieldset>
				</CompactCard>
			</div>
		);
	}
} );