import { createSelector } from 'reselect';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import getErrors from './errors';
import _ from 'lodash';

export default createSelector(
	getErrors,
	( state ) => state.shippingLabel.form,
	( errors, form ) => (
		! _.isEmpty( form ) &&
		! hasNonEmptyLeaves( errors ) &&
		! form.origin.normalizationInProgress &&
		! form.destination.normalizationInProgress &&
		! form.rates.retrievalInProgress &&
		! _.isEmpty( form.rates.available )
	)
);
