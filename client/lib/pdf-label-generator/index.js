import { translate as __ } from 'lib/mixins/i18n';

const PDF_DENSITY = 72; // dots per inch

const LABEL_SIZE = {
	width: 4 * PDF_DENSITY,
	height: 6 * PDF_DENSITY,
};

// Measures from https://github.com/devongovett/pdfkit/blob/master/lib/page.coffee#L72
export const PAPER_SIZES = {
	[ __( 'A4' ) ]: [ 595.28, 841.89 ],
	[ __( 'Letter' ) ]: [ 612.00, 792.00 ],
	[ __( 'Legal' ) ]: [ 612.00, 1008.00 ],
	[ __( 'Label (4"x6")' ) ]: [ LABEL_SIZE.width, LABEL_SIZE.height ],
};

export default ( paperSize, labels ) => {
	return new Promise( ( resolve ) => {
		const text = `Rendering ${labels.length} labels in ${paperSize} paper`;
		const blob = new Blob( [ `<p>${text}</p>` ], { type: 'text/html' } );
		const url = URL.createObjectURL( blob );
		setTimeout( () => resolve( url ), 1000 );
	} );
};
