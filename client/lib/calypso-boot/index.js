import ReactClass from 'react/lib/ReactClass';
import i18n from 'i18n-calypso';

export default function boot() {
	// Initialize i18n mixin
	ReactClass.injection.injectMixin( i18n.mixin );

	if ( window.i18nLocaleStrings ) {
		const i18nLocaleStringsObject = window.i18nLocaleStrings;
		i18n.setLocale( i18nLocaleStringsObject );
	}
}

boot();
