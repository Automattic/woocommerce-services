import React from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormButton from 'components/forms/form-button';
import FormCheckbox from 'components/forms/form-checkbox';
import FormButtonsBar from 'components/forms/form-buttons-bar';
import FormInputValidation from 'components/forms/form-input-validation';
import CompactCard from 'components/card/compact';
import SelectOptGroups from 'components/forms/select-opt-groups';

class AddPackageDialog extends React.Component {

	render() {
		return (
			<div>
				<CompactCard>
					<FormSectionHeading>Add a package</FormSectionHeading>
					<FormFieldset>
						<FormLabel htmlFor="package_type">Type of package</FormLabel>
						<SelectOptGroups id="package_type" value="box" optGroups={ [
							{
								options: [
									{ value: 'box', label: 'Box' },
									{ value: 'envelope', label: 'Envelope' }
								]
							},
							{
								label: 'Saved packages',
								options: [
									{ value: 'bike-box', label: 'Bike box' },
									{ value: 'small-padded-envelope', label: 'Small padded envelope' }
								]
							},
							{
								label: 'USPS Flat Rate Boxes and Envelopes',
								options: [
									{ value: 'small-box', label: 'Priority Mail Small Flat Rate Box' },
									{ value: 'medium-box', label: 'Priority Mail Medium Flat Rate Box' },
									{ value: 'large-box', label: 'Priority Mail Large Flat Rate Box' },
									{ value: 'legal-envelope', label: 'Priority Mail Legal Flat Rate Envelope' }
								]
							}
						] } readOnly={ true } />
					</FormFieldset>
					<FormFieldset>
						<FormLabel htmlFor="package_name">Package name</FormLabel>
						<FormTextInput
							id="package_name"
							name="package_name"
							className="is-error"
							placeholder="The customer will see this during checkout" />
						<FormInputValidation isError text="A package name is needed" />
					</FormFieldset>
					<FormFieldset>
						<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
						<FormTextInput placeholder="100 x 25 x 5.5" />
						<a href="#">Define exterior dimensions</a>
					</FormFieldset>
					<FormFieldset>
						<div style={ { width: '200px', float: 'left' } }>
							<FormLabel htmlFor="package_weight">Package weight</FormLabel>
							<FormTextInput style={ { width: '88%' } } id="package_weight" name="package_weight" placeholder="Weight of box" />
						</div>
						<div style={ { width: '200px', float: 'left' } }>
							<FormLabel htmlFor="max_weight">Max weight</FormLabel>
							<FormTextInput style={ { width: '88%' } } id="max_weight" name="max_weight" placeholder="Max weight" /> lbs
						</div>
						<FormSettingExplanation style={ { display: 'inline-block' } }>Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
					</FormFieldset>
				</CompactCard>
				<CompactCard>
					<FormLabel>
						<FormCheckbox checked={ true } readOnly={ true } />
						<span>Save package to use in other shipping methods</span>
					</FormLabel>
					<FormButtonsBar>
						<FormButton>Add package</FormButton>
					</FormButtonsBar>
				</CompactCard>
			</div>
		);
	}

}

export default AddPackageDialog;
