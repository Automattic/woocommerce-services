import { translate as __ } from 'lib/mixins/i18n';
import querystring from 'querystring';
import _ from 'lodash';

const PAPER_SIZES = {
	a4: {
		name: __( 'A4' ),
		exclude: ( country ) => [ 'US', 'CA', 'MX', 'DO' ].includes( country ),
	},
	label: {
		name: __( 'Label (4"x6")' ),
	},
	legal: {
		name: __( 'Legal' ),
	},
	letter: {
		name: __( 'Letter' ),
	},
};

export const getPaperSizes = ( country ) => (
	_.reduce( PAPER_SIZES, ( result, { name, exclude }, key ) => {
		if ( ! exclude || ! exclude( country ) ) {
			result[ key ] = name;
		}
		return result;
	}, {} )
);

const _getPDFURL = ( paperSize, labels, orderId, isReprint, baseURL, nonce ) => {
	if ( ! PAPER_SIZES[ paperSize ] ) {
		throw new Error( `Invalid paper size: ${paperSize}` );
	}
	const params = {
		_wpnonce: nonce,
		paper_size: paperSize,
		order_id: orderId,
		is_reprint: isReprint || false,
		'label_ids[]': _.filter( _.map( labels, 'labelId' ) ),
		'captions[]': _.filter( _.map( labels, 'caption' ) ),
	};
	return baseURL + '?' + querystring.stringify( params );
};

export const getPrintURL = ( paperSize, labels, orderId, isReprint, { labelsPrintURL, nonce } ) => {
	return _getPDFURL( paperSize, labels, orderId, isReprint, labelsPrintURL, nonce );
};

export const getPreviewURL = ( paperSize, labels, { labelsPreviewURL, nonce } ) => {
	return _getPDFURL( paperSize, labels, 0, false, labelsPreviewURL, nonce );
};
