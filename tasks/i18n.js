const i18nCli = require( 'i18n-calypso/cli' );

i18nCli( {
	inputPaths: [ 'dist/woocommerce-services.js' ],
	output: 'i18n/strings.php',
	format: 'php',
	phpArrayName: 'i18nStrings',
	projectName: 'woocommerce-services',
	textdomain: 'woocommerce-services',
} );