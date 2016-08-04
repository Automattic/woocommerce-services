import * as FormActions from 'state/form/actions';
import printDocument from 'lib/utils/print-document';

export const OPEN_PRINTING_FLOW = 'OPEN_PRINTING_FLOW';
export const EXIT_PRINTING_FLOW = 'EXIT_PRINTING_FLOW';

export const openPrintingFlow = () => ( dispatch ) => {
	dispatch( { type: OPEN_PRINTING_FLOW } );
	dispatch( FormActions.setFormProperty( 'pristine', false ) );
	dispatch( FormActions.nextStep() );
};

export const exitPrintingFlow = () => ( dispatch ) => {
	dispatch( { type: EXIT_PRINTING_FLOW } );
	dispatch( FormActions.resetFlow() );
};

export const finishPrintingFlow = () => ( dispatch, getState ) => {
	dispatch( exitPrintingFlow() );
	printDocument( getState().form.response.url );
};
