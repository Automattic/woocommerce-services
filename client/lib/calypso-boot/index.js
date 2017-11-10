/**
 * External dependencies
 */
import i18n from 'i18n-calypso';

export default function boot() {
	if ( ! window.i18nLocale ) {
		return;
	}

	const i18nLocaleStringsObject = JSON.parse( window.i18nLocale.json );
	if ( ! i18nLocaleStringsObject || ! i18nLocaleStringsObject[ '' ] ) {
		return;
	}

	i18nLocaleStringsObject[ '' ].localeSlug = window.i18nLocale.localeSlug;
	i18n.setLocale( i18nLocaleStringsObject );
}

boot();
