/* eslint-disable import/no-nodejs-modules */
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

it( 'configures WordPress utilities before Jest loads the legacy suite', () => {
	const output = execFileSync( process.execPath, [ '-e', `
		require( './tests/e2e/config/jest.config' );
		console.log( JSON.stringify( {
			url: process.env.WP_BASE_URL,
			username: process.env.WP_USERNAME,
			password: process.env.WP_PASSWORD,
		} ) );
	` ], {
		cwd: path.resolve( __dirname, '../../..' ),
		env: {
			...process.env,
			NODE_CONFIG: JSON.stringify( {
				url: 'http://127.0.0.1:18084/',
				users: { admin: { username: 'custom-admin', password: 'custom-password' } },
			} ),
		},
		encoding: 'utf8',
	} );

	expect( JSON.parse( output.trim() ) ).toEqual( {
		url: 'http://127.0.0.1:18084',
		username: 'custom-admin',
		password: 'custom-password',
	} );
} );
