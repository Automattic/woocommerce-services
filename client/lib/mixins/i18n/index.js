import { sprintf } from 'sprintf-js';

// This is a replacement for Calypso's i18n mixin.
// It allows us to reuse Calypso components that depend on i18n as well
// as implement our own translation logic, that could involved the server.
let translations = {};

export const initialize = ( newTranslations ) => {
	translations = newTranslations;
};

export const translate = ( text, options = {} ) => {
	const translation = ( text in translations ) ? translations[ text ] : text;

	if ( options.args ) {
		return sprintf( translation, options.args );
	}

	return translation;
};

export const mixin = {
	translate,
};
