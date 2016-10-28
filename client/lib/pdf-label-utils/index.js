import { translate as __ } from 'lib/mixins/i18n';

export const PAPER_SIZES = {
	a4: __( 'A4' ),
	letter: __( 'Letter' ),
	legal: __( 'Legal' ),
	label: __( 'Label (4"x6")' ),
};

export const getPDFUrl = ( paperSize, labels, baseUrl, isPreview = false ) => {
	if ( ! PAPER_SIZES[ paperSize ] ) {
		throw new Error( `Invalid paper size: ${paperSize}` );
	}
	const params = [];
	params.push( `preview=${isPreview}` );
	params.push( `paper_size=${encodeURIComponent( paperSize )}` );
	labels.forEach( ( { caption, labelId } ) => {
		if ( labelId ) {
			params.push( `label_ids[]=${labelId}` );
		}
		if ( caption ) {
			params.push( `captions[]=${encodeURIComponent( caption )}` );
		}
	} );
	return baseUrl + '?' + params.join( '&' );
};
