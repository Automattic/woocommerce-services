/**
 * External dependencies
 */
import { get } from 'lodash';

export const getShippingSettingsForm = ( state ) => {
	return get( state, [ 'form' ], null );
};

export const isLoaded = ( state ) => {
	const form = getShippingSettingsForm( state );
	return form && form.loaded;
};

export const getFormSchema = ( state ) => {
	const form = getShippingSettingsForm( state );
	if ( ! form || ! form.loaded ) {
		return null;
	}
	return form.schema;
};

export const getStoreOptions = ( state ) => {
	const form = getShippingSettingsForm( state );
	if ( ! form || ! form.loaded ) {
		return null;
	}
	return form.storeOptions;
};

export const getFormLayout = ( state ) => {
	const form = getShippingSettingsForm( state );
	if ( ! form || ! form.loaded ) {
		return null;
	}
	return form.layout;
};
