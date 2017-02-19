import ReactClass from 'react/lib/ReactClass';
import { initialize, mixin } from '../mixins/i18n';

export default function boot() {
	// Initialize i18n
	let i18nLocaleStringsObject = {};

	if ( window.i18nLocaleStrings ) {
		i18nLocaleStringsObject = window.i18nLocaleStrings;
	}

	initialize( i18nLocaleStringsObject );

	ReactClass.injection.injectMixin( mixin );
}

boot();
