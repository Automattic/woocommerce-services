export const ADD_PACKAGE = 'ADD_PACKAGE';
export const EDIT_PACKAGE = 'EDIT_PACKAGE';

export const addPackage = () => ( {
	type: ADD_PACKAGE,
} );

export const editPackage = ( packageToEdit ) => ( {
	type: EDIT_PACKAGE,
	package: packageToEdit,
} );
