import { expect } from 'chai';
import {
	addPackage,
	editPackage,
	ADD_PACKAGE,
	EDIT_PACKAGE,
} from '../actions';

describe( 'Packages state actions', () => {
	it( '#addPackage()', () => {
		expect( addPackage() ).to.eql( {
			type: ADD_PACKAGE,
		} );
	} );

	it( '#editPackage()', () => {
		const packageToEdit = {
			name: 'Test box',
			dimensions: '10 x 13 x 6',
			is_letter: false,
		};
		expect( editPackage( packageToEdit ) ).to.eql( {
			type: EDIT_PACKAGE,
			package: packageToEdit,
		} );
	} );
} );
