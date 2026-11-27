const { CustomerFlow } = require( '../utils/flows' );
const { waitAndType } = require( '../vendor/puppeteer-utils/lib/page-utils' );

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
