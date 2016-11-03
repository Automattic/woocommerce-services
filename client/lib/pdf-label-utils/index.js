import { translate as __ } from 'lib/mixins/i18n';

export const PAPER_SIZES = {
	// a4: __( 'A4' ),
	// a5: __( 'A5' ),
	label: __( 'Label (4"x6")' ),
	legal: __( 'Legal' ),
	letter: __( 'Letter' ),
};

const _getPDFURL = ( paperSize, labels, baseURL, nonce ) => {
	if ( ! PAPER_SIZES[ paperSize ] ) {
		throw new Error( `Invalid paper size: ${paperSize}` );
	}
	const params = [];
	params.push( `_wpnonce=${nonce}` );
	params.push( `paper_size=${encodeURIComponent( paperSize )}` );
	labels.forEach( ( { caption, labelId } ) => {
		if ( labelId ) {
			params.push( `label_ids[]=${labelId}` );
		}
		if ( caption ) {
			params.push( `captions[]=${encodeURIComponent( caption )}` );
		}
	} );
	return baseURL + '?' + params.join( '&' );
};

export const getPrintURL = ( paperSize, labels, { labelsPrintURL, nonce } ) => {
	return _getPDFURL( paperSize, labels, labelsPrintURL, nonce );
};

export const getPreviewURL = ( paperSize, labels, { labelsPreviewURL, nonce } ) => {
	return _getPDFURL( paperSize, labels, labelsPreviewURL, nonce );
};
