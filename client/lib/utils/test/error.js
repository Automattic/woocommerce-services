import { expect } from 'chai';
import {
	isFieldError,
} from '../error';

describe( 'Error utils', () => {
	it( '#isFieldError(): required, no pattern, empty value', () => {
		const required = true;
		const schema = {
			pattern: '',
		};
		const value = '';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.true;
	} );

	it( '#isFieldError(): required, no pattern, not empty value', () => {
		const required = true;
		const schema = {
			pattern: '',
		};
		const value = '1234';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.false;
	} );

	it( '#isFieldError(): required, pattern, empty value', () => {
		const required = true;
		const schema = {
			pattern: /^1234/,
		};
		const value = '';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.true;
	} );

	it( '#isFieldError(): not required, no pattern, empty value', () => {
		const required = false;
		const schema = {
			pattern: '',
		};
		const value = '';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.false;
	} );

	it( '#isFieldError(): not required, pattern, empty value', () => {
		const required = false;
		const schema = {
			pattern: /^1234/,
		};
		const value = '';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.true;
	} );

	it( '#isFieldError(): not required, pattern, expected value', () => {
		const required = false;
		const schema = {
			pattern: /^1234/,
		};
		const value = '1234';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.false;
	} );

	it( '#isFieldError(): not required, pattern, not expected value', () => {
		const required = false;
		const schema = {
			pattern: /^1234/,
		};
		const value = '1235';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.true;
	} );

	it( '#isFieldError(): required, pattern, expected value', () => {
		const required = true;
		const schema = {
			pattern: /^1234/,
		};
		const value = '1234';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.false;
	} );

	it( '#isFieldError(): required, pattern, not expected value', () => {
		const required = true;
		const schema = {
			pattern: /^1234/,
		};
		const value = '1235';

		const isError = isFieldError( required, schema, value );

		expect( isError ).to.be.true;
	} );
} );
