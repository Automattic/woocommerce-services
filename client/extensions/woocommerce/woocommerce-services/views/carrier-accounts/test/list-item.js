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
import Button from 'components/button';
import { CarrierAccountListItem } from '../list-item';
import CarrierIcon from '../../../components/carrier-icon';
import Dialog from 'components/dialog';

configure( { adapter: new Adapter() } );

function createCarrierAccountListItemWrapper( { carrier = {} } ) {
	const props = {
		siteId: 10,
		translate: ( text ) => text,
		data: carrier,
		setVisibilityDisconnectCarrierDialog: jest.fn(),
		disconnectCarrier: jest.fn(),
	};

	const wrapper = shallow( <CarrierAccountListItem { ...props } /> );

	return { wrapper, props };
}

describe( 'Carrier Accounts List Item', () => {
	describe( 'for a disconnected carrier', () => {
		const carrier = {
			id: 'carrier',
			carrier: 'carrier',
			account: 'account',
		};

		const { wrapper } = createCarrierAccountListItemWrapper( {
			carrier: carrier,
		} );

		it( "renders the carrier's icon", function () {
			const renderedCarrierIcon = wrapper.find( CarrierIcon );
			expect( renderedCarrierIcon ).to.have.lengthOf( 1 );
		} );
		it( "renders the carrier's name", function () {
			const renderedCarrierName = wrapper.find( '.carrier-accounts__list-item-name' );
			expect( renderedCarrierName ).to.have.lengthOf( 1 );
		} );
		it( "does not render the carrier's credentials", function () {
			const renderedCarrierCredentials = wrapper.find( '.carrier-accounts__list-item-credentials' );
			expect( renderedCarrierCredentials ).to.have.lengthOf( 1 );
			expect( renderedCarrierCredentials.text() ).to.equal( 'account' );
		} );

		it( 'renders a connect button', function () {
			const renderedCarrierActions = wrapper.find( '.carrier-accounts__list-item-actions' );
			const connectButton = renderedCarrierActions.find( Button );
			expect( renderedCarrierActions ).to.have.lengthOf( 1 );
			expect( connectButton ).to.have.lengthOf( 1 );
			expect( connectButton.children().text() ).to.equal( 'Disconnect' );
		} );

		it( 'renders a hidden disconnect confirmation dialog', () => {
			const renderedDialog = wrapper.find( Dialog );
			expect( renderedDialog ).to.have.lengthOf( 1 );
			expect( renderedDialog.props().isVisible ).to.equal( false );
		} );
	} );
	describe( 'for a connected carrier', () => {
		const carrier = {
			id: 'carrier',
			carrier: 'carrier',
			account: 'carrier-credentials',
		};

		const { wrapper, props } = createCarrierAccountListItemWrapper( {
			carrier: carrier,
		} );
		const renderedCarrierName = wrapper.find( '.carrier-accounts__list-item-credentials' );
		const renderedCarrierActions = wrapper.find( '.carrier-accounts__list-item-actions' );
		const disconnectButton = renderedCarrierActions.find( Button );

		it( "renders the carrier's credentials", function () {
			expect( renderedCarrierName ).to.have.lengthOf( 1 );
			expect( renderedCarrierName.text() ).to.equal( 'carrier-credentials' );
		} );

		it( 'renders a disconnect button', function () {
			expect( renderedCarrierActions ).to.have.lengthOf( 1 );
			expect( disconnectButton ).to.have.lengthOf( 1 );
			expect( disconnectButton.children().text() ).to.equal( 'Disconnect' );
		} );

		describe( 'when the disconnect button is clicked', () => {
			disconnectButton.simulate( 'click' );
			props.showDisconnectDialog = true;
			wrapper.setProps( props );
			const renderedDialog = wrapper.find( Dialog );

			it( 'renders a dialog to confirm disconnection', () => {
				expect( props.setVisibilityDisconnectCarrierDialog.mock.calls ).to.have.lengthOf( 1 );
				expect( props.setVisibilityDisconnectCarrierDialog.mock.calls[ 0 ] ).to.deep.equal( [
					10,
					'carrier',
					true,
				] );
				expect( renderedDialog.props().isVisible ).to.equal( true );
			} );

			it( 'disconnects the carrier when the disconnect button is pressed', () => {
				const confirmDisconnectButton = renderedDialog.props().buttons[ 1 ];

				confirmDisconnectButton.props.onClick();
				expect( props.disconnectCarrier.mock.calls ).to.have.lengthOf( 1 );
				expect( props.disconnectCarrier.mock.calls[ 0 ] ).to.deep.equal( [ 10, 'carrier', 'carrier' ] );
			} );
		} );
	} );
} );
