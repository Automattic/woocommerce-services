/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { expect } from 'chai';
import { configure, shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';

/**
 * Internal dependencies
 */
import { RatesStep } from '../index';

configure( { adapter: new Adapter() } );

describe( 'RatesStep', () => {
	it( 'preselects a single rate with its carrier', () => {
		const updateRate = jest.fn();
		const rate = {
			service_id: 'Ground',
			carrier_id: 'ups',
			rate: 10,
		};

		shallow( <RatesStep
			siteId={ 10 }
			orderId={ 1000 }
			form={ { packages: { selected: { box_1: {} }, saved: true } } }
			allPackages={ {} }
			values={ { box_1: '' } }
			available={ { box_1: { default: { rates: [ rate ] } } } }
			errors={ {} }
			ratesTotal={ 0 }
			translate={ text => text }
			toggleStep={ jest.fn() }
			updateRate={ updateRate }
			shippingMethod=""
			shippingCost={ 0 }
		/> );

		expect( updateRate.mock.calls ).to.deep.equal( [
			[ 1000, 10, 'box_1', 'Ground', 'ups', false ],
		] );
	} );
} );
