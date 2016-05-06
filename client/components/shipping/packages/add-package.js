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

const getDialogButtons = ( mode, savePackage, packageData ) => {
	return [
		<FormLabel className="share-package-option">
			<FormCheckbox checked={ true } readOnly={ true } />
			<span>Save package to use in other shipping methods</span>
		</FormLabel>,
		<FormButton onClick={ () => savePackage( packageData ) }>
			{ ( 'add' === mode ) ? __( 'Add package' ) : __( 'Apply changes' ) }
		</FormButton>,
	];
};

const renderOuterDimensionsToggle = ( showOuterDimensions, packageData, toggleOuterDimensions ) => {
	if ( ! showOuterDimensions && ! packageData.outer_dimensions ) {
		return (
			<a
				href="#"
				className="form-setting-explanation"
				onClick={ ( evt ) => {
					evt.preventDefault();
					toggleOuterDimensions();
				} }>
				Define exterior dimensions
			</a>
		);
	}
};

const updateFormTextField = ( event, updatePackagesField ) => {
	const {
		name,
		value,
	} = event.target;
	updatePackagesField( { [name]: value } );
};

const renderOuterDimensions = ( showOuterDimensions, packageData, value, updatePackagesField, modalReadOnly ) => {
	return ( showOuterDimensions || packageData.outer_dimensions ) ? (
		<FormFieldset>
			<FormLabel>Outer Dimensions (L x W x H)</FormLabel>
			<FormTextInput
				name="outer_dimensions"
				placeholder="100.25 x 25.25 x 5.75"
				value={ value }
				onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
				disabled={ modalReadOnly }
			/>
		</FormFieldset>
	) : null;
};

const usePresetValues = ( preset, updatePackagesField, setModalReadOnly ) => {
	updatePackagesField( {
		index: null,
		...preset,
	} );
	setModalReadOnly( true );
};

const useDefaultField = ( value, updatePackagesField, setModalReadOnly ) => {
	updatePackagesField( {
		index: null,
		is_letter: 'envelope' === value ? true : false,
	} );
	setModalReadOnly( false );
};

const AddPackageDialog = ( props ) => {
	const {
		dismissModal,
		mode,
		presets,
		weightUnit,
		packageData,
		showOuterDimensions,
		toggleOuterDimensions,
		savePackage,
		updatePackagesField,
		modalReadOnly,
		setModalReadOnly,
	} = props;

	const {
		name,
		inner_dimensions,
		outer_dimensions,
		box_weight,
		max_weight,
	} = packageData;

	return (
		<Dialog
			isVisible={ true }
			additionalClassNames="wcc-modal wcc-shipping-add-edit-package-dialog"
			onClose={ dismissModal }
			buttons={ getDialogButtons( mode, savePackage, packageData ) }>
			<FormSectionHeading>{ ( 'edit' === mode ) ? __( 'Edit package' ) : __( 'Add a package' ) }</FormSectionHeading>
			{ ( 'add' === mode ) ? (
				<AddPackagePresets
					presets={ presets }
					onSelectDefault={ ( value ) => useDefaultField( value, updatePackagesField, setModalReadOnly ) }
					onSelectPreset={ ( idx ) => usePresetValues( presets.boxes[idx], updatePackagesField, setModalReadOnly ) }
				/>
			) : null }
			<FormFieldset>
				<FormLabel htmlFor="name">Package name</FormLabel>
				<FormTextInput
					id="name"
					name="name"
					placeholder="The customer will see this during checkout"
					value={ name }
					onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
				/>
			</FormFieldset>
			<FormFieldset>
				<FormLabel>Inner Dimensions (L x W x H)</FormLabel>
				<FormTextInput
					name="inner_dimensions"
					placeholder="100 x 25 x 5.5"
					value={ inner_dimensions }
					onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
					disabled={ modalReadOnly }
				/>
				{ renderOuterDimensionsToggle( showOuterDimensions, packageData, toggleOuterDimensions ) }
			</FormFieldset>
			{ renderOuterDimensions( showOuterDimensions, packageData, outer_dimensions, updatePackagesField, modalReadOnly ) }
			<FormFieldset className="wcc-shipping-add-package-weight-group">
				<div className="wcc-shipping-add-package-weight">
					<FormLabel htmlFor="box_weight">Package weight</FormLabel>
					<FormTextInput
						id="box_weight"
						name="box_weight"
						placeholder="Weight of box"
						value={ box_weight }
						onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
						disabled={ modalReadOnly }
					/>
				</div>
				<div className="wcc-shipping-add-package-weight">
					<FormLabel htmlFor="max_weight">Max weight</FormLabel>
					<FormTextInput
						id="max_weight"
						name="max_weight"
						placeholder="Max weight"
						value={ max_weight }
						onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
						disabled={ modalReadOnly }
					/>
					<span className="wcc-shipping-add-package-weight-unit">{ weightUnit }</span>
				</div>
				<FormSettingExplanation> Define both the weight of the empty box and the max weight it can hold</FormSettingExplanation>
			</FormFieldset>
		</Dialog>
	);
};

AddPackageDialog.propTypes = {
	dismissModal: PropTypes.func.isRequired,
	presets: PropTypes.object.isRequired,
	weightUnit: PropTypes.string.isRequired,
	mode: PropTypes.string.isRequired,
	updatePackagesField: PropTypes.func.isRequired,
	showOuterDimensions: PropTypes.bool,
	toggleOuterDimensions: PropTypes.func.isRequired,
	savePackage: PropTypes.func.isRequired,
	packageData: PropTypes.object,
	setModalReadOnly: PropTypes.func.isRequired,
	modalReadOnly: PropTypes.bool,
};

AddPackageDialog.defaultProps = {
	packageData: {},
};

export default AddPackageDialog;
