import { createSelector } from 'reselect';
import sum from 'lodash/sum';
import map from 'lodash/map';
import find from 'lodash/find';
import get from 'lodash/get';

export const getRatesTotal = createSelector(
	( rates ) => rates.values,
	( rates ) => rates.available,
	( selectedRates, availableRates ) => {
		const ratesCost = map( selectedRates, ( rateId, boxId ) => {
			const packageRates = get( availableRates, [ boxId, 'rates' ], false );

			if ( packageRates ) {
				const foundRate = find( packageRates, [ 'service_id', rateId ] );

				return foundRate ? foundRate.rate : 0;
			}
			return 0;
		} );

		return sum( ratesCost ).toFixed( 2 );
	}
);
