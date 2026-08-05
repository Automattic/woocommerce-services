/** @format */

describe( 'store-notices entry', () => {
	let registerPlugin;
	let hostCreateElement;
	const ExperimentalOrderMeta = () => null;

	beforeEach( () => {
		jest.resetModules();

		registerPlugin = jest.fn();
		hostCreateElement = jest.fn( ( type, props, ...children ) => ( {
			type,
			props,
			children,
			createdByHost: true,
		} ) );

		global.window.wp = {
			plugins: { registerPlugin },
			element: { createElement: hostCreateElement, useEffect: () => {} },
			data: { useDispatch: () => ( {} ), useSelect: () => [] },
		};
		global.window.wc = { blocksCheckout: { ExperimentalOrderMeta } };
	} );

	afterEach( () => {
		delete global.window.wp;
		delete global.window.wc;
	} );

	it( 'registers the store notices plugin for the checkout scope', () => {
		require( '../store-notices' );

		expect( registerPlugin ).toHaveBeenCalledTimes( 1 );
		expect( registerPlugin ).toHaveBeenCalledWith(
			'woocommerce-services-store-notices',
			expect.objectContaining( { scope: 'woocommerce-checkout' } )
		);
	} );

	it( 'creates elements with the host React (window.wp.element), not a bundled React', () => {
		// Share the entry's module registry so this is the same component instance
		// the entry renders.
		const { StoreNotices } = require( 'components/store/notices' );
		require( '../store-notices' );

		const { render } = registerPlugin.mock.calls[ 0 ][ 1 ];
		const element = render();

		// Every element handed to the host renderer must be created by the host
		// createElement. An element created by the plugin's bundled React is
		// rejected by React 19 ("A React Element from an older version of
		// React was rendered").
		expect( hostCreateElement ).toHaveBeenCalledTimes( 2 );
		expect( element.createdByHost ).toBe( true );
		expect( element.type ).toBe( ExperimentalOrderMeta );

		// The child counts too: building only the outer element with the host React
		// leaves the same React 19 rejection one level down.
		const child = element.children[ 0 ];
		expect( child.createdByHost ).toBe( true );
		expect( child.type ).toBe( StoreNotices );
	} );
} );
