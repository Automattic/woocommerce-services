/**
 * Internal dependencies
 */
import { clickReactButton } from '../../utils/index';
import { StoreOwnerFlow } from "../../utils/flows";
import { AccountWithNoCreditCard, AccountWithOneCreditCard, AccountWithTwoCreditCard } from "../../fixtures/account_settings";

// describe( 'Saving shipping label settings', () => {
// 	it( 'Can toggle shipping labels' , async () => {
// 		let slug = 'woocommerce-services';
// 		await StoreOwnerFlow.login();
// 		await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
//         await page.waitForSelector('.form-toggle__switch');
//         await expect(page).toClick('.form-toggle__switch');
//         await page.waitForSelector('.label-settings__cards-label form-label', {
//             hidden: true
//         });
//         await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
//         // Clicking save should persist the hidden state of the section.
//         await page.waitForSelector('.label-settings__cards-label form-label', {
//             hidden: true
//         });

//         // Enable
//         await expect(page).toClick('.form-toggle__switch');
//         await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
//     });
// } );

describe( 'Shipping label payment method', () => {
    afterEach(() => {
        page.removeAllListeners('request');
    });

    const mockAccountSettingAPI = async (response) => {
        await page.setRequestInterception(true);
        page.on('request', request => {
            if (request.url().match('wp-json/wc/v1/connect/account/settings')) {
                request.respond({
                    status: 200,
                    contentType: 'application/json; charset=UTF-8',
                    body: JSON.stringify(response)
                });
            } else {
                request.continue();
            }
        });
    };

    it('should show "Choose a different card" button if Wordpress.com has a credit card', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithOneCreditCard);

        // Go to settings page after setting up interception.
        await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await page.waitForSelector('.button.is-borderless', { text: 'Choose a different card' } );
        await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );
        await expect(page).toMatchElement('.label-settings__card-number', {
            text: 'VISA ****5959'
        });
        await expect(page).toMatchElement('.label-settings__card-name', {
            text: 'John Doe'
        });
    });

    it('should show "Add a credit card" button if Wordpress has no credit card', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithOneCreditCard);

        // No need to login again, refresh page
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await page.waitForSelector('.button.is-compact', { text: 'Add a credit card' } );
    });

    it('should show 2 credit cards with the right information f Wordpress.com has 2 credit cards', async () => {
        // Intercept API before making any HTTP request
        await mockAccountSettingAPI(AccountWithTwoCreditCard);

        // No need to login again, refresh page
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Test
        await page.waitForSelector('.button.is-borderless', { text: 'Choose a different card' } );
        await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );

        // Verify "Add another credit card" is present after clicking 'Choose a different card'
        await page.waitForSelector('.button.is-compact', { text: 'Add another credit card' } );

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
});