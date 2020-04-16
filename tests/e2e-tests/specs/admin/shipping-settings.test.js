/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from "../../utils/flows";
import { AccountWithNoCreditCard, AccountWithOneCreditCard, AccountWithTwoCreditCard, AccountWithTwoCreditCardAndNoDefault } from "../../fixtures/account_settings";

// Click save and wait until it's saved.
const saveAndWait = async () => {
    await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
    await page.waitForSelector('[class="button is-primary"]');
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

describe( 'Saving shipping label settings', () => {
    console.log( "Running 'Saving shipping label settings'" );

	it( 'Can toggle shipping labels' , async () => {
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await page.waitForSelector('.form-toggle__switch');
        await expect(page).toClick('.form-toggle__switch');
        await page.waitForSelector('.card.label-settings__labels-container', {
            visible: false
        });
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        // Clicking save should persist the visible state of the section.
        await page.waitForSelector('.card.label-settings__labels-container', {
            visible: false
        });

        // Toggle it back to enable so the following tests can run.
        await expect(page).toClick('.form-toggle__switch');
        await saveAndWait();
    });

    it ('Should be able to select a different paper size', async () => {
        // Save it as legal
        await page.select('select.form-select', 'legal');
        await saveAndWait();
        await expect(page).toMatchElement('.notice.is-success .notice__text', { text: 'Your shipping settings have been saved.' });

        let paperSize = await page.$('select.form-select');
        let selectedOption = await (await paperSize.getProperty('value')).jsonValue();
        expect(selectedOption).toBe('legal');

        // Save it back to label
        await page.select('select.form-select', 'label');
        await saveAndWait();
        await expect(page).toMatchElement('.notice.is-success .notice__text', { text: 'Your shipping settings have been saved.' });

        paperSize = await page.$('select.form-select');
        selectedOption = await (await paperSize.getProperty('value')).jsonValue();
        expect(selectedOption).toBe('label');
    });
} );

describe( 'Shipping label payment method', () => {
    console.log( "Running 'Shipping label payment method'" );

    let response;

    const accountSettingsRequestListener = (request) => {
        if (request.url().match('wp-json/wc/v1/connect/account/settings')) {
            request.respond({
                status: 200,
                contentType: 'application/json; charset=UTF-8',
                body: JSON.stringify(response)
            });
        } else {
            request.continue();
        }
    };

    const mockAccountSettingAPI = async (mockResponse) => {
        response = mockResponse;
        page.on('request', accountSettingsRequestListener);
    };

    afterEach(() => {
        response = '';
        page.removeListener('request', accountSettingsRequestListener);
    });

    /**
     * Not really a test case. But needed to run this once, prior to all test case in this describe.
     */
    it('should turn on request interception after all credit card test ran', async () => {
        await page.setRequestInterception(true);
    });

    it('should show "Add a credit card" button if Wordpress has no credit card', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithNoCreditCard);

        // Go to settings page after setting up interception.
        await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await waitForSelectorAndText('.button.is-compact', 'Add a credit card');
    });

    it('should show "Choose a different card" button if Wordpress.com has a credit card', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithOneCreditCard);

        // No need to login again, refresh page
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await waitForSelectorAndText('.button.is-borderless', 'Choose a different card');
        await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );
        await expect(page).toMatchElement('.label-settings__card-number', {
            text: 'VISA ****5959'
        });
        await expect(page).toMatchElement('.label-settings__card-name', {
            text: 'John Doe'
        });
    });

    it('should show 2 credit cards with the right information if Wordpress.com has 2 credit cards', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithTwoCreditCard);

        // No need to login again, refresh page
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await waitForSelectorAndText('.button.is-borderless', 'Choose a different card' );
        await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );

        // Verify "Add another credit card" is present after clicking 'Choose a different card'
        await waitForSelectorAndText('.button.is-compact', 'Add another credit card' );

        // The index is based on the order in the settings' API. Inside the payment_methods prop.
        // This API end point is mocked, check fixtures/account_settings.js for ordering.
        const VISA_CARD_INDEX = 0;
        const MASTERCARD_INDEX = 1;

        const cardNumbers = await page.$$('.label-settings__card-number');
        const firstCardNumber = await (await cardNumbers[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardNumber).toEqual('VISA ****5959');
        const secondCardNumber = await (await cardNumbers[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardNumber).toEqual('MasterCard ****2862');

        const cardNames = await page.$$('.label-settings__card-name');
        const firstCardName = await (await cardNames[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardName).toEqual('John Doe');
        const secondCardName = await (await cardNames[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardName).toEqual('Jane Smith');

        const cardExpiryDates = await page.$$('.label-settings__card-date');
        const firstCardExpiryDate = await (await cardExpiryDates[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardExpiryDate).toEqual('Expires 2030-06-10');
        const secondCardExpiryDate = await (await cardExpiryDates[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardExpiryDate).toEqual('Expires 2025-12-31');

        // Verify the default box is checked.
        const cardDefaultCheckbox = await page.$$('.label-settings__card-checkbox.form-checkbox');
        const firstCardDefaultCheckbox = await (await cardDefaultCheckbox[VISA_CARD_INDEX].getProperty('checked')).jsonValue();
        expect(firstCardDefaultCheckbox).toBeFalsy();
        const secondCardDefaultCheckbox = await (await cardDefaultCheckbox[MASTERCARD_INDEX].getProperty('checked')).jsonValue();
        expect(secondCardDefaultCheckbox).toBeTruthy();
    });

    it('should have no default card checked if there are multiple cards in Wordpress.com', async () => {
        await mockAccountSettingAPI(AccountWithTwoCreditCardAndNoDefault);

        // No need to login again, refresh page
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Verify "Add another credit card" is present after clicking 'Choose a different card'
        await waitForSelectorAndText('.button.is-compact', 'Add another credit card' );

        // The index is based on the order in the settings' API. Inside the payment_methods prop.
        // This API end point is mocked, check fixtures/account_settings.js for ordering.
        const VISA_CARD_INDEX = 0;
        const MASTERCARD_INDEX = 1;

        const cardNumbers = await page.$$('.label-settings__card-number');
        const firstCardNumber = await (await cardNumbers[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardNumber).toEqual('VISA ****5959');
        const secondCardNumber = await (await cardNumbers[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardNumber).toEqual('MasterCard ****2862');

        const cardNames = await page.$$('.label-settings__card-name');
        const firstCardName = await (await cardNames[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardName).toEqual('John Doe');
        const secondCardName = await (await cardNames[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardName).toEqual('Jane Smith');

        const cardExpiryDates = await page.$$('.label-settings__card-date');
        const firstCardExpiryDate = await (await cardExpiryDates[VISA_CARD_INDEX].getProperty('innerText')).jsonValue();
        expect(firstCardExpiryDate).toEqual('Expires 2030-06-10');
        const secondCardExpiryDate = await (await cardExpiryDates[MASTERCARD_INDEX].getProperty('innerText')).jsonValue();
        expect(secondCardExpiryDate).toEqual('Expires 2025-12-31');

        // Verify that no default box is checked.
        const cardDefaultCheckbox = await page.$$('.label-settings__card-checkbox.form-checkbox');
        const firstCardDefaultCheckbox = await (await cardDefaultCheckbox[VISA_CARD_INDEX].getProperty('checked')).jsonValue();
        expect(firstCardDefaultCheckbox).toBeFalsy();
        const secondCardDefaultCheckbox = await (await cardDefaultCheckbox[MASTERCARD_INDEX].getProperty('checked')).jsonValue();
        expect(secondCardDefaultCheckbox).toBeFalsy();
    });

    /**
     * Not really a test case. But needed to run this once, after all test case ran in this describe.
     */
    it('should turn off request interception after all credit card test ran', async () => {
        await page.setRequestInterception(false);
    });
});

describe( 'Packaging', () => {
    let metricSystemValue = ''; // either "in" or "cm".

    console.log("Running 'packaging'");

    it( '> Can add package' , async () => {
        console.log(">> Started 'Can add package'");
        const packageName = 'Package Box 5x5x5';

        console.log(">> Login");
		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click to pop up modal
        console.log('>> Wait for "Add package" to finish loading, click to pop up modal');
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button', { text: 'Add package' } );

        // Create a new package
        console.log('>> Create a new package');
        await waitForSelectorAndText( '.packages__add-edit-title.form-section-heading', 'Add a package' );
        await expect( page ).toFill( '.packages__properties-group #name', packageName );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__length', '5' );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__width', '5' );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__height', '5' );
        await expect( page ).toFill( '.form-text-input-with-affixes #box_weight', '0.5' );
        await expect( page ).toClick( '.button.form-button.is-primary', { text: 'Add package' } )

        // Set this once globally, all following test will use this metric
        const metricSystem = await page.$$('.form-text-input-with-affixes .form-text-input-with-affixes__suffix');
        metricSystemValue = await (await metricSystem[0].getProperty('innerText')).jsonValue();

        // Verify package shows up in list
        console.log('>> Verify package shows up in list');
        const detailsName = await page.$$('.packages__packages-row .packages__packages-row-details-name');
        const detailValue = await (await detailsName[0].getProperty('innerText')).jsonValue();
        console.log('>> details name: ', detailValue);

        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        console.log('>> details name matched.');

        const detailsDimension = await page.$$('.packages__packages-row .packages__packages-row-dimensions');
        const detailsDimensionValue = await (await detailsDimension[1].getProperty('innerText')).jsonValue();
        console.log('>> details dimension value: "' + detailsDimensionValue + '"');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "5 x 5 x 5 " + metricSystemValue });

        // Save package
        console.log('>> Save package');
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is saved.
        console.log('>> Refresh page and make sure it is saved.');
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "5 x 5 x 5 " + metricSystemValue });
        console.log(">> Finished 'Can add package'");
    });

    it( '> Can edit package' , async () => {
        console.log("Started 'Can edit package'");
        const packageName = 'Package Box 10x10x10';

        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click "edit" once this session is loaded
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button.is-compact', { text: 'Edit' } );

        // Edit package
        await waitForSelectorAndText( '.packages__add-edit-title.form-section-heading', 'Edit package' );
        await expect( page ).toFill( '.packages__properties-group #name', packageName );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__length', '10' );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__width', '10' );
        await expect( page ).toFill( '.form-text-input.form-dimensions-input__height', '10' );
        await expect( page ).toFill( '.form-text-input-with-affixes #box_weight', '0.8' );
        await expect( page ).toClick( '.button.form-button.is-primary', { text: 'Done' } )

        // Verify package shows up in list
        console.log('>> Verify package shows up in list');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });

        // Save package
        console.log('>> Save package');
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is updated.
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });
        console.log("Started 'Finish edit package'");
    });

    it( '> Can delete package' , async () => {
        console.log("Started 'Can delete package'");
        const packageName = 'Package Box 10x10x10';

        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click "edit" once this session is loaded
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button.is-compact', { text: 'Edit' } );

        // Delete package
        await page.waitForSelector( '.packages__add-edit-title.form-section-heading', { text: 'Edit package' } );
        await expect( page ).toClick( '.button.packages__delete.is-scary.is-borderless', { text: 'Delete this package' } )

        // Verify package is no longer in the list.
        console.log('>> Verify package shows up in list');
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });

        // Save package
        console.log('>> Save package');
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is deleted.
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });
        console.log("Started 'Finish delete package'");
    });
});