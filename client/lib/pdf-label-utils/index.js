/**
 * External dependencies
 */
import { translate as __ } from 'i18n-calypso';
import querystring from 'querystring';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import * as api from 'api';
import getPDFSupport from 'lib/utils/pdf-support';

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

const _getPDFURL = ( paperSize, labels ) => {
	if ( ! PAPER_SIZES[ paperSize ] ) {
		throw new Error( `Invalid paper size: ${ paperSize }` );
	}
	const params = {
		paper_size: paperSize,
		//send params as a CSV to avoid conflicts with some plugins out there (#1111)
		label_id_csv: _.filter( _.map( labels, 'labelId' ) ).join( ',' ),
		caption_csv: _.filter( _.map( labels, ( l ) => ( l.caption ? encodeURIComponent( l.caption ) : null ) ) ).join( ',' ),
	};

	return api.createGetUrlWithNonce( api.url.labelsPrint(), querystring.stringify( params ) );
};

export const getPrintURL = ( paperSize, labels ) => {
	return _getPDFURL( paperSize, labels );
};

export const getPreviewURL = ( paperSize, labels ) => {
	return getPDFSupport() ? _getPDFURL( paperSize, labels ) : null;
};
