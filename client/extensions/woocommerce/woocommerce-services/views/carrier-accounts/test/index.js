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
import CarrierAccounts from '../index.js';
import CarrierAccountListItem from '../list-item';
import Card from 'components/card';
import ExtendedHeader from 'woocommerce/components/extended-header';

configure( { adapter: new Adapter() } );

function createCarrierAccountsWrapper( { carriers = [] } ) {
	return shallow( <CarrierAccounts siteId={10} carriers={carriers} /> );
}

describe( 'Carrier Accounts', () => {
	describe( 'with a disconnected carrier', () => {
		const carrier = {
			id: 'carrier',
			carrier: 'carrier',
		};

		const wrapper = createCarrierAccountsWrapper( { carriers: [ carrier ] } );
		const renderedExtendedHeader = wrapper.find( ExtendedHeader );
		const renderedCard = wrapper.find( Card );
		const renderedCarrierListItem = wrapper.find( CarrierAccountListItem );
		const renderedCarrierListHeader = wrapper.find( '.carrier-accounts__header' );

		it( 'renders a title with a description', function () {
			expect( renderedExtendedHeader ).to.have.lengthOf( 1 );
			expect( renderedExtendedHeader.props().label ).to.equal( 'Carrier account' );
			expect( renderedExtendedHeader.props().description ).to.equal(
				'Set up your own carrier account by adding your credentials here'
			);
		} );

		it( 'renders a card', function () {
			expect( renderedCard ).to.have.lengthOf( 1 );
		} );

		it( 'the card renders a list of carriers', function () {
			expect( renderedCarrierListItem ).to.have.lengthOf( 1 );
		} );

		it( "doesn't render the credentials column in the carriers list header", function () {
			expect( renderedCarrierListHeader.find( '.carrier-accounts__header-credentials' ) ).to.have.lengthOf( 0 );
		} );

		it( 'renders a name column in the carrier accounts list header', function () {
			expect( renderedCarrierListHeader.find( '.carrier-accounts__header-name' ) ).to.have.lengthOf( 1 );
		} );
	} );
	describe( 'with a connected carrier', () => {
		const carrier = {
			id: 'carrier',
			carrier: 'carrier',
			account: 'carrier-credentials',
		};

		const wrapper = createCarrierAccountsWrapper( { carriers: [ carrier ] } );
		const renderedCarrierListHeader = wrapper.find( '.carrier-accounts__header' );

		it( 'renders the credentials column in the carriers list header', function () {
			expect( renderedCarrierListHeader.find( '.carrier-accounts__header-credentials' ) ).to.have.lengthOf( 1 );
		} );
	} );
	describe( 'with no carriers', () => {
		const wrapper = createCarrierAccountsWrapper( { carriers: [] } );
		const renderedCarrierListHeader = wrapper.find( '.carrier-accounts__header' );
		const renderedCard = wrapper.find( Card );

		it( 'does not render the carriers header', function () {
			expect( renderedCarrierListHeader ).to.have.lengthOf( 0 );
		} );
		it( 'does not render the carriers list', function () {
			expect( renderedCard ).to.have.lengthOf( 0 );
		} );
	} );
} );
