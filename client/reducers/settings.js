/**
 * External dependencies
 */
import { combineReducers } from 'redux';
import { UPDATE_TEXT_FIELD } from '../actions/settings';

export default function settings( state = {}, action ) {
    switch ( action.type ) {
        case UPDATE_TEXT_FIELD:
            return Object.assign( {}, state, {
                [action.key]: action.value
            } );
    }

    return state;
}