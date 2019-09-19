/** @format */
/**
 * Internal dependencies
 */
import { WOOCOMMERCE_SERVICES_SHIPPING_ACTION_LIST_CREATE } from 'woocommerce/state/action-types';

/**
 * Creates an action list to save WCS shipping settings (labels and packages)
 *
 * Saves the WCS settings
 * @param {Function} [successAction] Action to be dispatched upon successful completion.
 * @param {Function} [failureAction] Action to be dispatched upon failure of execution.
 * @param {Function} [noLabelsPaymentAction] Action to be dispatched if labels are enabled but no payment method was selected.
 * @param {Boolean}  [onlyPackages] Whether to just update packages or not (component from settings is re-used in order view).
 * @return {Object} Action object.
 */
export function createWcsShippingSaveActionList(
	successAction,
	failureAction,
	noLabelsPaymentAction,
	onlyPackages
) {
	return {
		type: WOOCOMMERCE_SERVICES_SHIPPING_ACTION_LIST_CREATE,
		successAction,
		failureAction,
		noLabelsPaymentAction,
		onlyPackages: onlyPackages || false,
	};
}
