/* eslint-disable no-console, import/no-nodejs-modules */
const i18nCli = require( 'i18n-calypso-cli' );
const glob = require('glob');

glob(__dirname + '/../dist/**/*.js', {}, (err, files) => {
	i18nCli( {
		inputPaths: files,
		output: 'i18n/strings.php',
		format: 'php',
		phpArrayName: 'i18nStrings',
		projectName: 'woocommerce-services',
		textdomain: 'woocommerce-services',
	} );

	i18nCli( {
		inputPaths: files,
		output: 'i18n/languages/woocommerce-services.pot',
		format: 'pot',
		phpArrayName: 'i18nStrings',
		projectName: 'woocommerce-services',
		textdomain: 'woocommerce-services',
	} );
})


