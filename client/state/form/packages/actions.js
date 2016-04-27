export const ADD_PACKAGE = 'ADD_PACKAGE';
export const EDIT_PACKAGE = 'EDIT_PACKAGE';
export const DISMISS_MODAL = 'DISMISS_MODAL';
export const SAVE_PACKAGE = 'SAVE_PACKAGE';
export const UPDATE_PACKAGES_FIELD = 'UPDATE_PACKAGES_FIELD';

export const addPackage = () => ( {
	type: ADD_PACKAGE,
} );

export const editPackage = ( packageToEdit ) => ( {
	type: EDIT_PACKAGE,
	package: packageToEdit,
} );

export const dismissModal = () => ( {
	type: DISMISS_MODAL,
} );

export const savePackage = ( settings_key, packageData ) => ( {
	type: SAVE_PACKAGE,
	settings_key,
	packageData,
} );

export const updatePackagesField = ( newValues ) => ( {
	type: UPDATE_PACKAGES_FIELD,
	values: newValues,
} );
