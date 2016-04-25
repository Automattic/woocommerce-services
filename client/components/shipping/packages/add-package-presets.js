import React, { PropTypes } from 'react';
import SelectOptGroups from 'components/forms/select-opt-groups';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';

const defaultPackages = {
	label: 'Custom box',
	options: [
		{
			value: 'default_box',
			label: 'Box',
		},
		{
			value: 'default_envelope',
			label: 'Envelope',
		},
	],
};

const getOptionGroups = ( presets ) => {
	return [
		defaultPackages,
		{
			label: presets.title,
			options: presets.boxes.map( ( box, idx ) => {
				return {
					value: 'preset_' + idx,
					label: box.name,
				};
			} ),
		},
	]
};

const handleSelectEvent = ( e, selectDefault, selectPreset ) => {
	const parts = e.target.value.split( '_' );
	const type = parts[0];
	const id = parts[1];
	switch ( type ) {
		case 'default':
			return selectDefault( id );
		case 'preset':
			return selectPreset( Number.parseInt( id ) );
	}
};

const AddPackagePresets = ( { presets, onSelectDefault, onSelectPreset } ) => {
	return (
		<FormFieldset>
			<FormLabel htmlFor="package_type">Type of package</FormLabel>
			<SelectOptGroups
				id="package_type"
				onClick={ e => handleSelectEvent( e, onSelectDefault, onSelectPreset ) }
				onChange={ () => {} }
				optGroups={ getOptionGroups( presets ) }
				readOnly={ false }/>
		</FormFieldset>
	);
};

AddPackagePresets.propTypes = {
	presets: PropTypes.object.isRequired,
	onSelectDefault: PropTypes.func.isRequired,
	onSelectPreset: PropTypes.func.isRequired,
};

export default AddPackagePresets;
