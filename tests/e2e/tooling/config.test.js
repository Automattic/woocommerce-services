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

it( 'accepts local WordPress origins and recovers CSS with the configured safe parser', () => {
	const output = execFileSync( process.execPath, [ '-e', `
		const config = require( './webpack.config' );
		const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
		const minimizer = config.optimization.minimizer.find( plugin => plugin instanceof CssMinimizerPlugin );
		const origins = [ 'http://localhost:8050', 'http://localhost', 'http://127.0.0.1:8888',
			'https://localhost:8050', 'https://example.com', 'http://localhost.example.com', 'null', '' ];
		CssMinimizerPlugin.cssnanoMinify( { 'fixture.css': '.example { color: red' }, undefined,
			minimizer.options.minimizer.options ).then( result => console.log( JSON.stringify( {
				css: result.code,
				headers: origins.map( origin => config.devServer.headers( { headers: { origin } } ) ),
			} ) ) );
	` ], {
		cwd: path.resolve( __dirname, '../../..' ),
		env: { ...process.env, NODE_ENV: 'production' },
		encoding: 'utf8',
	} );
	const result = JSON.parse( output.trim() );
	expect( result.css ).toBe( '.example{color:red}' );
	for ( const [ index, origin ] of [ 'http://localhost:8050', 'http://localhost', 'http://127.0.0.1:8888', 'https://localhost:8050' ].entries() ) {
		expect( result.headers[ index ] ).toEqual( { 'Access-Control-Allow-Origin': origin, Vary: 'Origin' } );
	}
	for ( const headers of result.headers.slice( 4 ) ) {
		expect( headers ).not.toHaveProperty( 'Access-Control-Allow-Origin' );
	}
} );
