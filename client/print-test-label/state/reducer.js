import { UPDATE_PAPER_SIZE, PRINTING_IN_PROGRESS, PRINTING_ERROR } from './actions';

const reducers = {};

reducers[ UPDATE_PAPER_SIZE ] = ( state, { paperSize } ) => {
	return { ...state, paperSize };
};

reducers[ PRINTING_IN_PROGRESS ] = ( state, { inProgress } ) => {
	const newState = { ...state,
		printingInProgress: inProgress,
	};
	if ( inProgress ) {
		delete newState.error;
	}
	return newState;
};

reducers[ PRINTING_ERROR ] = ( state, { error } ) => {
	return { ...state,
		printingInProgress: false,
		error: error.toString(),
	};
};

export default ( state = {}, action ) => {
	if ( reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};
