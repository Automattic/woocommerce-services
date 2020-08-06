/* eslint-disable no-console, import/no-nodejs-modules */
const fs = require('fs');
const request = require('request');

const download = function(uri, filename, callback){
		request(uri).pipe(fs.createWriteStream(filename)).on('close', callback);
};

const base_url            = 'https://translate.wordpress.org/projects/wp-plugins/woocommerce-services/stable/';
const filename_prefix     = __dirname + '/../i18n/languages/woocommerce-services-';
const supported_languages = {
	'ar': 'ar',
	'es-mx': 'es_MX',
	'es-ve': 'es_VE',
	'es': 'es_ES',
	'fr-ca': 'fr_CA',
	'ja': 'ja',
	'nl': 'nl_NL',
	'ru': 'ru',
	'pt-br': 'pt_BR',
	'ro': 'ro_RO',
	'sv': 'sv_SE',
	'zh-cn': 'zh_CN',
};

for ( const locale in supported_languages ) {
	const fileLocale = supported_languages[ locale ];
	for ( const format of [ 'po', 'mo' ] ) {
		download( base_url + locale + '/default/export-translations/?format=' + format, filename_prefix + fileLocale + '.' + format, () => {
			console.log('Downloaded ' + fileLocale + '.' + format);
		} );
	}
}
