export const ADD_PACKAGE = 'ADD_PACKAGE';
export const EDIT_PACKAGE = 'EDIT_PACKAGE';
export const DISMISS_MODAL = 'DISMISS_MODAL';

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
