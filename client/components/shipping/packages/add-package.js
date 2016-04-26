import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormButton from 'components/forms/form-button';
import FormCheckbox from 'components/forms/form-checkbox';
import Dialog from 'components/dialog';
import AddPackagePresets from './add-package-presets';
import { translate as __ } from 'lib/mixins/i18n';

class AddPackageDialog extends React.Component {

	constructor() {
		super();
		this.state = {
			hideOuterDimensions: true,
		};
	}

	addPackage() {
		// TODO: dimensions are ignored for now
		const form = this.state.form;
		this.props.saveBox( {
			name: form.name,
			is_letter: form.is_letter,
			outer_length: 0,
			outer_width: 0,
			outer_height: 0,
			inner_length: 0,
			inner_width: 0,
			inner_height: 0,
		} );
		this.props.onClose();
	}

	getDialogButtons() {
		const {
			mode,
			addPackage,
		} = this.props;
		return [
			<FormLabel className="share-package-option">
				<FormCheckbox checked={ true } readOnly={ true } />
				<span>Save package to use in other shipping methods</span>
			</FormLabel>,
			<FormButton onClick={ () => addPackage() }>{ ( 'add' === mode ) ? __( 'Add package' ) : __( 'Apply changes' ) }</FormButton>,
		];
	}

	renderOuterDimensionsToggle() {
		if ( this.state.hideOuterDimensions ) {
			return (
				<a
					href="#"
					className="form-setting-explanation"
					onClick={ ( evt ) => {
						evt.preventDefault();
						this.setState( { hideOuterDimensions: false } );
					} }>
					Define exterior dimensions
				</a>
			);
		}
	}

	renderOuterDimensions( value ) {
		return this.state.hideOuterDimensions ? null : (
			<FormFieldset>
				<FormLabel>Outer Dimensions (L x W x H)</FormLabel>
				<FormTextInput
					name="outer_dimensions"
					placeholder="100.25 x 25.25 x 5.75"
					value={ value }
					onChange={ ( event ) => this.updateFormTextField( 'outer_dimensions', event.target.value ) }
				/>
			</FormFieldset>
		);
	}

	usePresetValues( idx ) {
		const preset = this.props.presets.boxes[idx];
		const newForm = {
			name: preset.name,
			inner_dimensions: preset.inner_length + ' x ' + preset.inner_width + ' x ' + preset.inner_height,
			outer_dimensions: preset.outer_length + ' x ' + preset.outer_width + ' x ' + preset.outer_height,
			package_weight: preset.box_weight,
			max_weight: preset.max_weight,
			is_letter: preset.is_letter || false,
		};
		this.setState( { form: newForm } );
	}

	updateFormTextField( field, value ) {
		const newForm = Object.assign( {}, this.state.form );
		newForm[field] = value;
		this.setState( { form: newForm } );
	}

	useDefaultField( value ) {
		const newForm = {
			name: '',
			inner_dimensions: '',
			outer_dimensions: '',
			package_weight: '',
			max_weight: '',
			is_letter: 'envelope' === value ? true : false,
		};
		this.setState( { form: newForm } );
	}

	render() {
		const {
			onClose,
			mode,
			presets,
			weightUnit,
			packageData,
		} = this.props;
		const {
			name,
			inner_dimensions,
			outer_dimensions,
			package_weight,
			max_weight,
		} = packageData;
		return (
			<Dialog
				isVisible={ true }
				additionalClassNames="wcc-modal wcc-shipping-add-edit-package-dialog"
				onClose={ onClose }
				buttons={ this.getDialogButtons() }>
				<FormSectionHeading>{ ( 'edit' === mode ) ? __( 'Edit package' ) : __( 'Add a package' ) }</FormSectionHeading>
				{ ( 'add' === mode ) ? (
					<AddPackagePresets
						presets={ presets }
						onSelectDefault={ ( value ) => this.useDefaultField( value ) }
						onSelectPreset={ ( idx ) => this.usePresetValues( idx ) }
					/>
				) : null }
				<FormFieldset>
					<FormLabel htmlFor="package_name">Package name</FormLabel>
					<FormTextInput
						id="package_name"
						name="package_name"
						placeholder="The customer will see this during checkout"
						value={ name }
						onChange={ ( event ) => this.updateFormTextField( 'name', event.target.value ) }
					/>
				</FormFieldset>
				<FormFieldset>
					<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
					<FormTextInput
						name="inner_dimensions"
						placeholder="100 x 25 x 5.5"
						value={ inner_dimensions }
						onChange={ ( event ) => this.updateFormTextField( 'inner_dimensions', event.target.value ) }
					/>
					{ this.renderOuterDimensionsToggle() }
				</FormFieldset>
				{ this.renderOuterDimensions( outer_dimensions ) }
				<FormFieldset className="wcc-shipping-add-package-weight-group">
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="package_weight">Package weight</FormLabel>
						<FormTextInput
							id="package_weight"
							name="package_weight"
							placeholder="Weight of box"
							value={ package_weight }
							onChange={ ( event ) => this.updateFormTextField( 'package_weight', event.target.value ) }
						/>
					</div>
					<div className="wcc-shipping-add-package-weight">
						<FormLabel htmlFor="max_weight">Max weight</FormLabel>
						<FormTextInput
							id="max_weight"
							name="max_weight"
							placeholder="Max weight"
							value={ max_weight }
							onChange={ ( event ) => this.updateFormTextField( 'max_weight', event.target.value ) }
						/>
						<span className="wcc-shipping-add-package-weight-unit">{ weightUnit }</span>
					</div>
					<FormSettingExplanation> Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
				</FormFieldset>
			</Dialog>
		);
	}
}

AddPackageDialog.propTypes = {
	onClose: PropTypes.func.isRequired,
	saveBox: PropTypes.func.isRequired,
	presets: PropTypes.object.isRequired,
	weightUnit: PropTypes.string.isRequired,
};

AddPackageDialog.defaultProps = {
	packageData: {}
};

export default AddPackageDialog;
