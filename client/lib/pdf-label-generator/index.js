import { translate as __ } from 'lib/mixins/i18n';

const PDF_DENSITY = 72; // dots per inch

const LABEL_SIZE = {
	width: 4 * PDF_DENSITY,
	height: 6 * PDF_DENSITY,
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

export default ( paperSize, labels ) => {
	return new Promise( ( resolve ) => {
		const text = `Rendering ${labels.length} labels in ${paperSize} paper`;
		const blob = new Blob( [ `<p>${text}</p>` ], { type: 'text/html' } );
		const url = URL.createObjectURL( blob );
		setTimeout( () => resolve( url ), 1000 );
	} );
};
