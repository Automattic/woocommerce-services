const i18nCli = require( 'i18n-calypso/cli' );

i18nCli( {
	inputPaths: [ 'dist/woocommerce-services-' + process.env.npm_package_version + '.js' ],
	output: 'i18n/strings.php',
	format: 'php',
	phpArrayName: 'i18nStrings',
	projectName: 'woocommerce-services',
	textdomain: 'woocommerce-services',
} );

i18nCli( {
	inputPaths: [ 'dist/woocommerce-services-' + process.env.npm_package_version + '.js' ],
	output: 'i18n/languages/woocommerce-services.pot',
	format: 'pot',
	phpArrayName: 'i18nStrings',
	projectName: 'woocommerce-services',
	textdomain: 'woocommerce-services',
} );
