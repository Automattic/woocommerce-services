import React, { PropTypes } from 'react';
import FormSectionHeading from 'components/forms/form-section-heading';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextInput from 'components/forms/form-text-input';
import FormInputValidation from 'components/forms/form-input-validation';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import FormButton from 'components/forms/form-button';
import Dialog from 'components/dialog';
import AddPackagePresets from './add-package-presets';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import modalErrors from './modal-errors';

const getDialogButtons = ( mode, dismissModal, savePackage, packageData, error ) => {
	return [
		<FormButton onClick={ () => savePackage( packageData ) } disabled={ error }>
			{ ( 'add' === mode ) ? __( 'Add package' ) : __( 'Apply changes' ) }
		</FormButton>,
		<FormButton onClick={ () => dismissModal() } isPrimary={ false }>
			{ __( 'Cancel' ) }
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
				{ __( 'View exterior dimensions' ) }
			</a>
		);
	}
};

const exampleDimensions = ( length, width, height, locale ) => {
	return length.toLocaleString( locale ) + ' x ' + width.toLocaleString( locale ) +
		' x ' + height.toLocaleString( locale );
};

const updateFormTextField = ( event, updatePackagesField ) => {
	const {
		name,
		value,
	} = event.target;
	updatePackagesField( { [name]: value } );
};

const renderOuterDimensions = ( showOuterDimensions, dimensionUnit, packageData, value, updatePackagesField, is_user_defined, error ) => {
	return ( showOuterDimensions || packageData.outer_dimensions ) ? (
		<FormFieldset>
			<FormLabel>{ sprintf( __( 'Outer Dimensions (L x W x H) %s' ), dimensionUnit ) }</FormLabel>
			<FormTextInput
				name="outer_dimensions"
				placeholder={ exampleDimensions( 100.25, 25.25, 5.75 ) }
				value={ value }
				className={ is_user_defined ? '' : 'flat-rate-package__outer-dimensions__read-only' }
				onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
				disabled={ ! is_user_defined }
				isError={ error }
			/>
		</FormFieldset>
	) : null;
};

const usePresetValues = ( preset, updatePackagesField ) => {
	updatePackagesField( {
		index: null,
		is_user_defined: false,
		...preset,
	} );
};

const useDefaultField = ( value, updatePackagesField ) => {
	updatePackagesField( {
		index: null,
		is_letter: 'envelope' === value,
		name: null,
		is_user_defined: true,
		outer_dimensions: null,
		inner_dimensions: null,
		box_weight: null,
		max_weight: null,
	} );
};

const AddPackageDialog = ( props ) => {
	const {
		showModal,
		dismissModal,
		mode,
		presets,
		dimensionUnit,
		weightUnit,
		packageData,
		showOuterDimensions,
		toggleOuterDimensions,
		savePackage,
		updatePackagesField,
		selectedPreset,
		setSelectedPreset,
		packages,
	} = props;

	const {
		name,
		inner_dimensions,
		outer_dimensions,
		box_weight,
		max_weight,
		is_user_defined,
	} = packageData;

	const boxNames = packages.map( ( boxPackage ) => boxPackage.name );
	const errors = modalErrors( packageData, boxNames );

	return (
		<Dialog
			isVisible={ showModal }
			additionalClassNames="wcc-modal wcc-shipping-add-edit-package-dialog"
			onClose={ dismissModal }
			buttons={ getDialogButtons( mode, dismissModal, savePackage, packageData, errors.any ) }>
			<FormSectionHeading>{ ( 'edit' === mode ) ? __( 'Edit package' ) : __( 'Add a package' ) }</FormSectionHeading>
			{ ( 'add' === mode ) ? (
				<AddPackagePresets
					selectedPreset={ selectedPreset }
					setSelectedPreset={ setSelectedPreset }
					presets={ presets }
					onSelectDefault={ ( value ) => useDefaultField( value, updatePackagesField ) }
					onSelectPreset={ ( idx ) => usePresetValues( presets.boxes[idx], updatePackagesField ) }
				/>
			) : null }
			<FormFieldset>
				<FormLabel htmlFor="name">{ __( 'Package name' ) }</FormLabel>
				<FormTextInput
					id="name"
					name="name"
					placeholder={ __( 'The customer will see this during checkout' ) }
					value={ name }
					onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
					isError={ errors.name }
				/>
				{ errors.name ? <FormInputValidation isError text={ errors.name } /> : '' }
			</FormFieldset>
			<FormFieldset>
				<FormLabel>{ sprintf( __( 'Inner Dimensions (L x W x H) %s' ), dimensionUnit ) }</FormLabel>
				<FormTextInput
					name="inner_dimensions"
					placeholder={ exampleDimensions( 100, 25, 5.5 ) }
					value={ inner_dimensions }
					className={ is_user_defined ? '' : 'flat-rate-package__inner-dimensions__read-only' }
					onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
					disabled={ ! is_user_defined }
					isError={ errors.inner_dimensions }
				/>
				{ renderOuterDimensionsToggle( showOuterDimensions, packageData, toggleOuterDimensions ) }
			</FormFieldset>
			{ renderOuterDimensions( showOuterDimensions, dimensionUnit, packageData, outer_dimensions, updatePackagesField, is_user_defined, errors.outer_dimensions ) }
			<FormFieldset className="wcc-shipping-add-package-weight-group">
				<div className="wcc-shipping-add-package-weight">
					<FormLabel htmlFor="box_weight">{ __( 'Package weight' ) }</FormLabel>
					<FormTextInput
						id="box_weight"
						name="box_weight"
						placeholder={ __( 'Weight of box' ) }
						value={ box_weight }
						className={ is_user_defined ? '' : 'flat-rate-package__package-weight__read-only' }
						onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
						disabled={ ! is_user_defined }
						isError={ errors.box_weight }
					/>
				</div>
				<div className="wcc-shipping-add-package-weight">
					<FormLabel htmlFor="max_weight">{ __( 'Max weight' ) }</FormLabel>
					<FormTextInput
						id="max_weight"
						name="max_weight"
						placeholder={ __( 'Max weight' ) }
						value={ max_weight }
						className={ is_user_defined ? '' : 'flat-rate-package__max-weight__read-only' }
						onChange={ ( event ) => updateFormTextField( event, updatePackagesField ) }
						disabled={ ! is_user_defined }
						isError={ errors.max_weight }
					/>
					<span className="wcc-shipping-add-package-weight-unit">{ weightUnit }</span>
				</div>
				<FormSettingExplanation>
					{ __( 'Defines both the weight of the empty box and the max weight it can hold' ) }
				</FormSettingExplanation>
			</FormFieldset>
		</Dialog>
	);
};

AddPackageDialog.propTypes = {
	dismissModal: PropTypes.func.isRequired,
	presets: PropTypes.object,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	mode: PropTypes.string.isRequired,
	updatePackagesField: PropTypes.func.isRequired,
	showOuterDimensions: PropTypes.bool,
	toggleOuterDimensions: PropTypes.func.isRequired,
	savePackage: PropTypes.func.isRequired,
	packageData: PropTypes.object,
	setSelectedPreset: PropTypes.func.isRequired,
	selectedPreset: PropTypes.string,
	packages: PropTypes.array.isRequired,
};

AddPackageDialog.defaultProps = {
	packageData: {
		is_user_defined: true,
	},
	mode: 'add',
};

export default AddPackageDialog;
