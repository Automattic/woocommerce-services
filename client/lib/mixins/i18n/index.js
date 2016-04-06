// This is a replacement for Calypso's i18n mixin.
// It allows us to reuse Calypso components that depend on i18n as well
// as implement our own translation logic, that could involved the server.
export default {
	translate: ( text ) => ( text ),
};
