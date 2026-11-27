const { CustomerFlow } = require( '../utils/flows' );
const { waitAndType, clickAndWaitForNewPage } = require( '../vendor/puppeteer-utils/lib/page-utils' );

it( 'uses the shipping flow XPath selector and observes the cart update', async () => {
	await page.setContent( '<ul><li class="type-product"><a><h2>Test product</h2></a>' +
		'<a class="add_to_cart_button ajax_add_to_cart" onclick="this.classList.add(\'added\')">Add</a></li></ul>' );
	await CustomerFlow.addToCartFromShopPage( 'Test product' );
	await expect( page ).toMatchElement( '.add_to_cart_button.added', { text: 'Add' } );
} );

it( 'replaces a field value using the vendored typing helper', async () => {
	await page.setContent( '<input id="address" value="Old address">' );
	await waitAndType( page, '#address', 'New address' );
	expect( await page.$eval( '#address', input => input.value ) ).toBe( 'New address' );
} );

it( 'opens a new tab and removes the browser target listener', async () => {
	await page.setContent( '<a id="new-tab" href="about:blank" target="_blank">Open</a>' );
	const listenersBefore = browser.listenerCount( 'targetcreated' );
	const openedPage = await clickAndWaitForNewPage( page, '#new-tab', 2000 );
	try {
		expect( openedPage ).not.toBe( page );
		expect( openedPage.url() ).toBe( 'about:blank' );
		expect( browser.listenerCount( 'targetcreated' ) ).toBe( listenersBefore );
	} finally {
		await openedPage.close();
	}
} );

it( 'cleans up the browser listener when no new tab opens', async () => {
	await page.setContent( '<button id="no-tab">Stay here</button>' );
	const listenersBefore = browser.listenerCount( 'targetcreated' );
	await expect( clickAndWaitForNewPage( page, '#no-tab', 200 ) ).rejects.toBe( 'Timed out in 200ms.' );
	expect( browser.listenerCount( 'targetcreated' ) ).toBe( listenersBefore );
} );
