/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * Internal dependencies
 */
import { HTTP_REQUEST } from 'state/action-types';

const actionsToModify = [];

/**
 * Look for Jetpack proxy requests that were localized as
 * indicated by the `meta.localApiRequest` flag.
 *
 * Modify their `dataLayer` meta to mimic the Jetpack proxy.
 *
 * @returns {Function} middleware function
 */
export default () => ( next ) => ( action ) => {
	if (
		HTTP_REQUEST === action.type &&
		_.get( action, 'meta.localApiRequest' ) &&
		_.has( action, 'onSuccess.type' )
	) {
		// This request was a Jetpack proxy request that
		// was rewritten to hit the local WC API.
		actionsToModify.push( _.get( action, 'onSuccess.type' ) );
	} else if (
		-1 !== actionsToModify.indexOf( action.type ) &&
		_.has( action, 'meta.dataLayer.data.body' )
	) {
		// The WooCommerce middleware expects this response from the
		// Jetpack REST API proxy, which nests the body under `data`.
		action.meta.dataLayer.data.data = action.meta.dataLayer.data.body;
		delete action.meta.dataLayer.data.body;

		// Don't process this action again.
		const actionIndex = actionsToModify.indexOf( action.type );
		actionsToModify.splice( actionIndex, 1 );
	}

	return next( action );
};
