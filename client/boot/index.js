import debug from 'debug';
import ReactInjection from 'react/lib/ReactInjection';
import i18n from 'lib/mixins/i18n';

const bootDebug = debug( 'woocommerce-connect-client:boot' );

const boot = () => {
	let i18nLocaleStringsObject = null;

	bootDebug( 'Starting WooCommerce Connect Support' );

	// Initialize i18n
	if ( window.i18nLocaleStrings ) {
		i18nLocaleStringsObject = JSON.parse( window.i18nLocaleStrings );
	}
	i18n.initialize( i18nLocaleStringsObject );

	ReactInjection.Class.injectMixin( i18n.mixin );
};

export default boot;
