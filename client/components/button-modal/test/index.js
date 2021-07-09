/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { mount } from 'enzyme';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ButtonModal from '..';

describe( 'ButtonModal', () => {
	describe( 'when isVisible true', () => {
		const props = {
			isVisible: true,
		}
		const wrapper = mount( <ButtonModal { ...props } /> );
		it( 'modal displayed', () => {
			expect( wrapper.find( 'Modal' ).length ).toBe( 1 );
		} );
		it( 'no buttons displayed for empty buttons prop', () => {
			expect( wrapper.find( 'button' ).length ).toBe( 0 );
		} );
	} );
	describe( 'for isVisible false', () => {
		const props = {
			isVisible: false,
		}
		const wrapper = mount( <ButtonModal { ...props } /> );
		it( 'modal not rendered', () => {
			expect( wrapper.find( 'Modal' ).length ).toBe( 0 );
		} );
	} );
	describe( 'when buttons are passed as generic objects', () => {
		const props = {
			isVisible: true,
			buttons: [ { label: '1', action: 'yes' }, { label: '2', action: 'no' } ],
		}
		const wrapper = mount( <ButtonModal { ...props } /> );
		it( '2 buttons rendered', () => {
			expect( wrapper.find( 'button' ).length ).toBe( 2 );
		} );
	} );
	describe( 'when buttons are passed as Button components', () => {
		const props = {
			isVisible: true,
			buttons: [ <Button />, <Button /> ],
		}
		const wrapper = mount( <ButtonModal { ...props } /> );
		it( '2 buttons rendered', () => {
			expect( wrapper.find( 'button' ).length ).toBe( 2 );
		} );
	} );
} );
