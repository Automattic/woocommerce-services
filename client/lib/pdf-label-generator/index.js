import { translate as __ } from 'lib/mixins/i18n';
import blobStream from 'blob-stream';
import PDF from 'pdfkit';
import _ from 'lodash';

const PDF_DENSITY = 72; // dots per inch

const MARGIN = 0.5 * PDF_DENSITY;

const FONT_SIZE = 24;

const PARAGRAPH_MARGIN = 12;

const LABEL_SIZE = {
	width: 4 * PDF_DENSITY,
	height: 6 * PDF_DENSITY,
};

const LABEL_SIZE_WITH_CAPTION = {
	width: LABEL_SIZE.width,
	height: LABEL_SIZE.height + FONT_SIZE + PARAGRAPH_MARGIN,
};

const LABEL_SIZE_WITH_CAPTION_AND_MARGIN = {
	width: LABEL_SIZE_WITH_CAPTION.width + MARGIN * 2,
	height: LABEL_SIZE_WITH_CAPTION.height + MARGIN * 2,
};

// Dimensions from https://github.com/devongovett/pdfkit/blob/master/lib/page.coffee#L72
export const PAPER_SIZES = {
	a4: {
		name: __( 'A4' ),
		dimensions: [ 595.28, 841.89 ],
	},
	letter: {
		name: __( 'Letter' ),
		dimensions: [ 612.00, 792.00 ],
	},
	legal: {
		name: __( 'Legal' ),
		dimensions: [ 612.00, 1008.00 ],
	},
	label: {
		name: __( 'Label (4"x6")' ),
		dimensions: [ LABEL_SIZE.width, LABEL_SIZE.height ],
	},
};

// TODO: If we implement batched purchase & printing, make this an LRU cache
const IMAGES_CACHE = {};

const fetchImage = ( url, nonce ) => {
	if ( IMAGES_CACHE[ url ] ) {
		return Promise.resolve( { url, buffer: IMAGES_CACHE[ url ] } );
	}
	return fetch( url, {
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
		},
	} )
	.then( ( response ) => {
		if ( 200 !== response.status ) {
			throw new Error( response.statusText );
		}
		return response.arrayBuffer();
	} )
	.then( ( buffer ) => {
		IMAGES_CACHE[ url ] = buffer;
		return { url, buffer };
	} );
};

const fits2LabelsInPage = ( dimensions ) => {
	return dimensions[ 0 ] >= LABEL_SIZE_WITH_CAPTION_AND_MARGIN.height &&
		dimensions[ 1 ] >= LABEL_SIZE_WITH_CAPTION_AND_MARGIN.width * 2;
};

const fitsCaption = ( dimensions ) => {
	return dimensions[ 0 ] >= LABEL_SIZE_WITH_CAPTION_AND_MARGIN.width &&
		dimensions[ 1 ] >= LABEL_SIZE_WITH_CAPTION_AND_MARGIN.height;
};

const renderPDF = ( paperSize, labels ) => {
	const dimensions = PAPER_SIZES[ paperSize ].dimensions;
	const doc = new PDF( {
		size: dimensions,
		margin: 0,
	} );
	doc.fontSize( FONT_SIZE );
	const stream = doc.pipe( blobStream() );

	const renderLabel = ( caption, imageBuffer, pageDimensions, offset ) => {
		const labelSize = caption ? LABEL_SIZE_WITH_CAPTION : LABEL_SIZE;
		const labelX = ( pageDimensions[ 0 ] - labelSize.width ) / 2 - offset[ 0 ];
		let labelY = ( pageDimensions[ 1 ] - labelSize.height ) / 2 - offset[ 1 ];
		if ( caption ) {
			doc.text( caption, labelX, labelY, { width: LABEL_SIZE.width, align: 'center' } );
			labelY += FONT_SIZE + PARAGRAPH_MARGIN;
		}
		doc.image( imageBuffer, labelX, labelY, { fit: PAPER_SIZES.label.dimensions } );
	};

	const renderSingleLabelInPage = ( { caption, imageBuffer }, index ) => {
		if ( 0 !== index ) {
			doc.addPage();
		}
		renderLabel( caption, imageBuffer, dimensions, [ 0, 0 ] );
	};

	if ( 1 < labels.length && fits2LabelsInPage( dimensions ) ) {
		labels.forEach( ( { caption, imageBuffer }, index ) => {
			const even = 0 === index % 2;
			if ( even ) {
				if ( 0 !== index ) {
					doc.addPage();
				}
				doc.rotate( 90, { origin: [ dimensions[ 0 ] / 2, dimensions[ 1 ] / 2 ] } );
			}
			const pageDimensions = [ dimensions[ 1 ] / 2, dimensions[ 0 ] ];
			const offset = [ ( dimensions[ 1 ] - dimensions[ 0 ] ) / 2, ( dimensions[ 0 ] - dimensions[ 1 ] ) / 2 ];
			if ( ! even ) {
				offset[ 0 ] -= pageDimensions[ 0 ];
			}
			renderLabel( caption, imageBuffer, pageDimensions, offset );
		} );
	} else if ( fitsCaption( dimensions ) ) {
		labels.forEach( renderSingleLabelInPage );
	} else {
		labels.map( ( { imageBuffer } ) => ( { imageBuffer } ) ).forEach( renderSingleLabelInPage );
	}

	doc.end();
	return new Promise( ( resolve ) => {
		stream.on( 'finish', function() {
			resolve( stream.toBlobURL( 'application/pdf' ) );
		} );
	} );
};

export default ( paperSize, labels, nonce ) => {
	const downloadTasks = _.uniq( _.map( labels, 'imageURL' ) ).map( ( url ) => fetchImage( url, nonce ) );
	return Promise.all( downloadTasks )
		.then( ( results ) => _.keyBy( results, 'url' ) )
		.then( ( images ) => {
			labels.forEach( ( label ) => label.imageBuffer = images[ label.imageURL ].buffer );
			return renderPDF( paperSize, labels );
		} );
};
