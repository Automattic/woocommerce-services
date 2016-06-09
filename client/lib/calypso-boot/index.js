import ReactInjection from 'react/lib/ReactInjection';
import i18n from '../mixins/i18n';

export default function boot() {
	// Initialize i18n
	let i18nLocaleStringsObject = {};

	if ( window.i18nLocaleStrings ) {
		i18nLocaleStringsObject = window.i18nLocaleStrings;
	}

	i18n.initialize( i18nLocaleStringsObject );

	ReactInjection.Class.injectMixin( i18n.mixin );
}

boot();
