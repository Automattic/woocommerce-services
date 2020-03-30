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
    // it('should show "Choose a different card" button if Wordpress.com has a credit card', async () => {
    //     await page.setRequestInterception(true);
    //     page.on('request', request => {
    //         if (request.url().match('wp-json/wc/v1/connect/account/settings')) {
    //             request.respond({
    //                 status: 200,
    //                 contentType: 'application/json; charset=UTF-8',
    //                 body: JSON.stringify(AccountWithOneCreditCard)
    //             });
    //         } else {
    //             request.continue();
    //         }
    //     });

    //     await StoreOwnerFlow.login();
    //     await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
    //     await page.waitForSelector('.button.is-borderless', { text: 'Choose a different card' } );
    //     await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );
    //     await expect(page).toMatchElement('.label-settings__card-number', {
    //         text: 'VISA ****5959'
    //     });
    //     await expect(page).toMatchElement('.label-settings__card-name', {
    //         text: 'John Doe'
    //     });
    // });

    // it('should show "Add a credit card" button if Wordpress has no credit card', async () => {
    //     await page.setRequestInterception(true);
    //     page.on('request', request => {
    //         if (request.url().match('wp-json/wc/v1/connect/account/settings')) {
    //             request.respond({
    //                 status: 200,
    //                 contentType: 'application/json; charset=UTF-8',
    //                 body: JSON.stringify(AccountWithNoCreditCard)
    //             });
    //         } else {
    //             request.continue();
    //         }
    //     });

    //     await StoreOwnerFlow.login();
    //     await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
    //     await page.waitForSelector('.button.is-compact', { text: 'Add a credit card' } );
    // });

    it('should show "Choose a different card" button if Wordpress.com has 2 credit cards', async () => {
        console.log(AccountWithTwoCreditCard);
        await page.setRequestInterception(true);
        page.on('request', request => {
            if (request.url().match('wp-json/wc/v1/connect/account/settings')) {
                request.respond({
                    status: 200,
                    contentType: 'application/json; charset=UTF-8',
                    body: JSON.stringify(AccountWithTwoCreditCard)
                });
            } else {
                request.continue();
            }
        });

        await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await page.waitForSelector('.button.is-borderless', { text: 'Choose a different card' } );
        await expect( page ).toClick( '.button.is-borderless', { text: 'Choose a different card' } );
        await expect(page).toMatchElement('.label-settings__card-number', {
            text: 'MasterCard ****2862'
        });
        await expect(page).toMatchElement('.label-settings__card-name', {
            text: 'John Doe'
        });
    });
});