/* eslint-disable no-console, import/no-nodejs-modules */
const fs      = require( 'fs' );
const request = require( 'request' );
const { version } = require( '../package.json' );

const REQUEST_DELAY_MS = 1500; // pause between every download to stay under burst limit
const RETRY_BASE_MS = 30000; // first retry wait; subsequent retries double (exponential backoff)
const MAX_RETRIES   = 5;

const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

// Fetch locale -> last-updated timestamp map from the WP translations API (single request).
const fetchTranslationMeta = () => new Promise( ( resolve, reject ) => {
	const url = 'https://api.wordpress.org/translations/plugins/1.0/?slug=woocommerce-services&version=' + version;
	request( { uri: url, json: true }, ( error, response, body ) => {
		if ( error ) return reject( error );
		if ( response.statusCode !== 200 ) return reject( new Error( 'Translations API returned HTTP ' + response.statusCode ) );
		const map = {};
		for ( const entry of ( body.translations || [] ) ) {
			map[ entry.language ] = new Date( entry.updated );
		}
		resolve( map );
	} );
} );

const isUpToDate = ( filename, remoteUpdated ) => {
	try {
		const { mtimeMs } = fs.statSync( filename );
		return mtimeMs >= remoteUpdated.getTime();
	} catch ( e ) {
		return false; // file does not exist
	}
};

// Returns false if file is missing, empty, or contains an HTML error response.
// .mo validity is checked via the binary magic number; .po validity via a text scan.
const isValidTranslationFile = ( filename ) => {
	try {
		const buf = fs.readFileSync( filename );
		if ( buf.length === 0 ) return false;
		if ( filename.endsWith( '.mo' ) ) {
			// MO magic: 0x950412de (LE) or 0xde120495 (BE) when read as LE uint32
			const magic = buf.readUInt32LE( 0 );
			return magic === 0x950412de || magic === 0xde120495;
		}
		// PO files are UTF-8 text; a 429 response starts with '<html'
		return ! /<html/i.test( buf.slice( 0, 256 ).toString( 'utf8' ) );
	} catch ( e ) {
		return false;
	}
};

const download = ( uri, filename ) => new Promise( ( resolve, reject ) => {
	request( { uri, encoding: null }, ( error, response, body ) => {
		if ( error ) return reject( error );
		if ( response.statusCode !== 200 ) {
			return reject( new Error( 'HTTP ' + response.statusCode + ' downloading ' + uri ) );
		}
		fs.writeFile( filename, body, ( err ) => {
			if ( err ) return reject( err );
			resolve();
		} );
	} );
} );

const downloadWithRetry = async ( uri, filename ) => {
	for ( let attempt = 0; attempt <= MAX_RETRIES; attempt++ ) {
		try {
			await download( uri, filename );
			return;
		} catch ( err ) {
			if ( err.message.startsWith( 'HTTP 429' ) && attempt < MAX_RETRIES ) {
				const delay = RETRY_BASE_MS * Math.pow( 2, attempt );
				console.warn( 'Rate limited (attempt ' + ( attempt + 1 ) + '/' + MAX_RETRIES + '), retrying in ' + ( delay / 1000 ) + 's...' );
				await sleep( delay );
			} else {
				throw err;
			}
		}
	}
};

const base_url        = 'https://translate.wordpress.org/projects/wp-plugins/woocommerce-services/stable/';
const filename_prefix = __dirname + '/../translations/woocommerce-services-';

const supported_languages = {
	'ar':    'ar',
	'es-mx': 'es_MX',
	'es-ve': 'es_VE',
	'es':    'es_ES',
	'fr-ca': 'fr_CA',
	'ja':    'ja',
	'nl':    'nl_NL',
	'ru':    'ru_RU',
	'pt-br': 'pt_BR',
	'ro':    'ro_RO',
	'sv':    'sv_SE',
	'zh-cn': 'zh_CN',
};

( async () => {
	let translationMeta = {};
	try {
		translationMeta = await fetchTranslationMeta();
		console.log( 'Fetched translation metadata for ' + Object.keys( translationMeta ).length + ' locales.' );
	} catch ( err ) {
		console.warn( 'Could not fetch translation metadata (' + err.message + '), will download all files.' );
	}

	for ( const locale in supported_languages ) {
		const fileLocale   = supported_languages[ locale ];
		const remoteUpdate = translationMeta[ fileLocale ] || null;

		for ( const format of [ 'po' ] ) {
			const url      = base_url + locale + '/default/export-translations/?format=' + format;
			const filename = filename_prefix + fileLocale + '.' + format;

			if ( isValidTranslationFile( filename ) && ( remoteUpdate ? isUpToDate( filename, remoteUpdate ) : true ) ) {
				console.log( 'Skipped ' + fileLocale + '.' + format + ' (up to date)' );
				continue;
			}

			try {
				await downloadWithRetry( url, filename );
				console.log( 'Downloaded ' + fileLocale + '.' + format );
			} catch ( err ) {
				console.error( 'Failed ' + fileLocale + '.' + format + ': ' + err.message );
			}
			await sleep( REQUEST_DELAY_MS );
		}
	}
	console.log( 'Translation download complete. Run the i18n-json task to generate .json files.' );
} )();
