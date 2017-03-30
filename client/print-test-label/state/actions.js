import { getPreviewURL } from 'lib/pdf-label-utils';
import printDocument from 'lib/utils/print-document';
import { translate as __ } from 'lib/mixins/i18n';

export const UPDATE_PAPER_SIZE = 'UPDATE_PAPER_SIZE';
export const PRINTING_IN_PROGRESS = 'PRINTING_IN_PROGRESS';
export const PRINTING_ERROR = 'PRINTING_ERROR';

export const updatePaperSize = ( paperSize ) => {
	return { type: UPDATE_PAPER_SIZE, paperSize };
};

export const print = () => ( dispatch, getState, { labelsPreviewURL, nonce } ) => {
	dispatch( { type: PRINTING_IN_PROGRESS, inProgress: true } );
	const { paperSize } = getState();

	const labelData = [
		{ caption: __( 'TEST LABEL 1' ) },
	];
	if ( 'label' !== paperSize ) {
		labelData.push( { caption: __( 'TEST LABEL 2' ) } );
	}

	printDocument( getPreviewURL( paperSize, labelData, { labelsPreviewURL, nonce } ) )
		.then( () => {
			dispatch( { type: PRINTING_IN_PROGRESS, inProgress: false } );
		} )
		.catch( ( error ) => {
			dispatch( { type: PRINTING_ERROR, error } );
		} );
};
