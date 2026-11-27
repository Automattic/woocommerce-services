/* eslint-disable import/no-nodejs-modules */
const fs = require( 'fs' );
const http = require( 'http' );
const path = require( 'path' );
const { version } = require( '../../../package.json' );

const dist = path.resolve( __dirname, '../../../dist' );
let server;
let origin;

beforeAll( async () => {
	if ( ! fs.existsSync( path.join( dist, `woocommerce-services-${ version }.js` ) ) ) {
		throw new Error( 'Run npm run dist before testing the production bundle.' );
	}
	server = http.createServer( ( request, response ) => {
		const url = new URL( request.url, 'http://localhost' );
		if ( url.pathname === '/' ) {
			const args = JSON.stringify( {
				paperSize: 'letter', storeOptions: { origin_country: 'US' },
				isShippingLoaded: url.searchParams.get( 'shipping' ) !== '0',
			} );
			response.setHeader( 'Content-Type', 'text/html' );
			response.end( `<!doctype html><body class="wp-core-ui wp-admin">
				<div id="outside-plugin" style="color:var(--color-primary, rgb(1, 2, 3))">Outside</div>
				<div class="wcc-root wc-connect-admin-test-print" data-args='${ args }'></div>
				<script>window.wcsPluginData = { assetPath: '/plugin/dist/' };</script>
				<script src="/jquery.js"></script>
				<script src="/plugin/dist/woocommerce-services-${ version }.js"></script>
			</body>` );
			return;
		}
		const file = url.pathname === '/jquery.js' ? require.resolve( 'jquery' ) :
			path.resolve( dist, url.pathname.replace( /^\/plugin\/dist\//, '' ) );
		if ( ( file.startsWith( dist + path.sep ) || file === require.resolve( 'jquery' ) ) && fs.existsSync( file ) && fs.statSync( file ).isFile() ) {
			response.setHeader( 'Content-Type', file.endsWith( '.css' ) ? 'text/css' : 'application/javascript' );
			fs.createReadStream( file ).pipe( response );
		} else {
			response.writeHead( 404 ).end();
		}
	} );
	await new Promise( resolve => server.listen( 0, '127.0.0.1', resolve ) );
	origin = `http://127.0.0.1:${ server.address().port }`;
} );

afterAll( async () => {
	if ( server ) {
		server.closeAllConnections();
		await new Promise( resolve => server.close( resolve ) );
	}
} );

it( 'boots the production bundle and lazy shipping assets from a relocated path', async () => {
	const errors = [];
	const onError = error => errors.push( error.message );
	page.on( 'pageerror', onError );
	try {
		await page.goto( origin );
		expect( errors ).toEqual( [] );
		await page.waitForSelector( '.print-test-label__paper-size', { timeout: 5000 } );
		expect( await page.$eval( '#outside-plugin', element => getComputedStyle( element ).color ) ).toBe( 'rgb(1, 2, 3)' );
		expect( await page.evaluate( () => getComputedStyle( document.documentElement ).getPropertyValue( '--color-primary' ) ) ).toBe( '' );
		const options = await page.$$eval( '.print-test-label__paper-size option', nodes => nodes.map( node => node.value ) );
		await page.select( '.print-test-label__paper-size', options[ 1 ] );
		expect( await page.evaluate( async () => ( await window.wcsGetAppStoreAsync( 'wc-connect-admin-test-print' ) ).getState().paperSize ) ).toBe( options[ 1 ] );
		expect( await page.$$eval( 'link[rel=stylesheet]', links => links.some( link => link.href.includes( '/plugin/dist/chunks/' ) ) ) ).toBe( true );
		expect( errors ).toEqual( [] );
	} finally {
		page.off( 'pageerror', onError );
	}
} );

it( 'keeps the test-label UI hidden when shipping is disabled', async () => {
	// Prove that this route renders before checking its disabled state.
	await page.goto( origin );
	await page.waitForSelector( '.print-test-label__paper-size', { timeout: 5000 } );
	await page.goto( origin + '/?shipping=0' );
	await page.waitForFunction( () => typeof window.wcsGetAppStoreAsync === 'function', { timeout: 5000 } );
	const state = await page.evaluate( async () => ( await window.wcsGetAppStoreAsync( 'wc-connect-admin-test-print' ) ).getState() );
	expect( state ).toMatchObject( { paperSize: 'letter', isShippingLoaded: false } );
	expect( await page.$( '.print-test-label__paper-size' ) ).toBeNull();
} );

it( 'does not emit global root variables in any CSS asset', () => {
	const cssFiles = fs.readdirSync( dist, { recursive: true } ).filter( name => name.endsWith( '.css' ) );
	expect( cssFiles.length ).toBeGreaterThan( 0 );
	for ( const name of cssFiles ) {
		expect( fs.readFileSync( path.join( dist, name ), 'utf8' ) ).not.toMatch( /:root\s*\{/ );
	}
} );
