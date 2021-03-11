/**
 * Internal dependencies
 */
const flows = require( './flows' );

/**
 * Click a tab (on post type edit screen).
 *
 * @param {string} tabName Tab label.
 */
const clickTab = async ( tabName ) => {
	await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Save changes on a WooCommerce settings page.
 */
const settingsPageSaveChanges = async () => {
	await page.focus( 'button.woocommerce-save-button' );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		page.click( 'button.woocommerce-save-button' ),
	] );
};

/**
 * Set checkbox.
 *
 * @param {string} selector
 */
const setCheckbox = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxElement = await checkbox.getProperty( 'checked' );
	const checkboxStatus = await checkboxElement.jsonValue();
	if ( checkboxStatus !== true ) {
		await page.click( selector );
	}
};

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
};

/**
 * Verify that checkbox is set.
 *
 * @param {string} selector Selector of the checkbox that needs to be verified.
 */
const verifyCheckboxIsSet = async( selector ) => {
	await page.focus( selector );
	const checkbox = await page.$( selector );
	const checkboxElement = await checkbox.getProperty( 'checked' );
	const checkboxStatus = await checkboxElement.jsonValue();
	await expect( checkboxStatus ).toBe( true );
};

const clickReactButton = async( selector ) => {
    await page.waitForSelector( selector );
    //await page.click( selector );
    await page.$eval( selector, elem => {
    	if ( !elem ) {
    		console.log( `.clickReactButton => Element not found: ${ selector }` );
    	}
    	return elem.click();
    } );
};

/**
 * This function will wait for a button with any CSS selector + text value.
 *
 * @param {string} selector CSS selector
 * @param {string} text The text value of the element we want to search for. ie. button's value, div's innertext.
 */
const waitForSelectorAndText = async (selector, text) => {
    return await page.waitForFunction(
        (cssSelector, innerTextContent) => !!Array.from(document.querySelectorAll(cssSelector)).find(el => el.textContent.trim() === innerTextContent.trim()),
        {},
        selector, text
    );
};

module.exports = {
	...flows,
	clickTab,
	settingsPageSaveChanges,
	setCheckbox,
	uiUnblocked,
	verifyCheckboxIsSet,
	clickReactButton,
	waitForSelectorAndText,
};
