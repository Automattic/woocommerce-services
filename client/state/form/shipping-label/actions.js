export const OPEN_PRINTING_FLOW = 'OPEN_PRINTING_FLOW';
export const EXIT_PRINTING_FLOW = 'EXIT_PRINTING_FLOW';

export const openPrintingFlow = () => ( {
	type: OPEN_PRINTING_FLOW,
} );

export const exitPrintingFlow = () => ( {
	type: EXIT_PRINTING_FLOW,
} );
