import { combineReducers } from 'redux';
import settings from './settings';
import form from './form';

const rootReducer = combineReducers( {
	settings,
	form,
} );

export default rootReducer;
