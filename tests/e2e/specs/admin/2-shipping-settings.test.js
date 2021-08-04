/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from "../../utils/flows";
import { waitForSelectorAndText } from "../../utils/index";
import { deleteAllPackages, saveAndWait } from '../../utils/components';
import { AccountWithNoCreditCard, AccountWithOneCreditCard, AccountWithTwoCreditCard, AccountWithTwoCreditCardAndNoDefault } from "../../fixtures/account_settings";


describe( 'Saving shipping label settings', () => {
	it( 'Can toggle shipping labels' , async () => {
		await StoreOwnerFlow.login();
		await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await page.waitForSelector('.components-form-toggle__input');
        await expect(page).toClick('.components-form-toggle__input');
        await page.waitForSelector('.card.label-settings__labels-container', {
            visible: false
        });

        await saveAndWait();

		await page.waitForSelector('.card.label-settings__labels-container', {
            visible: false
        });

        await expect(page).toClick('.components-form-toggle__input');
        await saveAndWait();
    });

    it ('Should be able to select a different paper size', async () => {
        // Save it as legal
        await page.select('select.form-select', 'legal');
        await saveAndWait();
        await expect(page).toMatchElement('.notice.is-success .notice__text', { text: 'Your shipping settings have been saved.' });

        let paperSize = await page.$('select.form-select');
		let paperSizeElement = await paperSize.getProperty('value');
        let selectedOption = await paperSizeElement.jsonValue();
        await expect(selectedOption).toBe('legal');

        // Save it back to label
        await page.select('select.form-select', 'label');
        await saveAndWait();
        await expect(page).toMatchElement('.notice.is-success .notice__text', { text: 'Your shipping settings have been saved.' });

        paperSize = await page.$('select.form-select');
		paperSizeElement = await paperSize.getProperty('value');
        selectedOption = await paperSizeElement.jsonValue();
        await expect(selectedOption).toBe('label');
    });
} );

describe( 'Shipping label payment method', () => {
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
		const firstCardNumberElement = await cardNumbers[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardNumber = await firstCardNumberElement.jsonValue();
        await expect(firstCardNumber).toEqual('VISA ****5959');
		const secondCardNumberElement = await cardNumbers[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardNumber = await secondCardNumberElement.jsonValue();
        await expect(secondCardNumber).toEqual('MasterCard ****2862');

        const cardNames = await page.$$('.label-settings__card-name');
		const firstCardNameElement = await cardNames[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardName = await firstCardNameElement.jsonValue();
        await expect(firstCardName).toEqual('John Doe');
		const secondCardNameElement = await cardNames[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardName = await secondCardNameElement.jsonValue();
        await expect(secondCardName).toEqual('Jane Smith');

        const cardExpiryDates = await page.$$('.label-settings__card-date');
		const firstCardExpiryDateElement = await cardExpiryDates[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardExpiryDate = await firstCardExpiryDateElement.jsonValue();
        await expect(firstCardExpiryDate).toEqual('Expires 2030-06-10');
		const secondCardExpiryDateElement = await cardExpiryDates[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardExpiryDate = await secondCardExpiryDateElement.jsonValue();
        await expect(secondCardExpiryDate).toEqual('Expires 2025-12-31');

        // Verify the default box is checked.
        const cardDefaultCheckbox = await page.$$('.label-settings__card-checkbox input');
		const firstCardDefaultCheckboxElement = await cardDefaultCheckbox[VISA_CARD_INDEX].getProperty('checked');
        const firstCardDefaultCheckbox = await firstCardDefaultCheckboxElement.jsonValue();
        await expect(firstCardDefaultCheckbox).toBeFalsy();
		const secondCardDefaultCheckboxElement = await cardDefaultCheckbox[MASTERCARD_INDEX].getProperty('checked');
        const secondCardDefaultCheckbox = await secondCardDefaultCheckboxElement.jsonValue();
        await expect(secondCardDefaultCheckbox).toBeTruthy();
    });

    it('should have no default card checked if there are multiple cards in Wordpress.com', async () => {
        await mockAccountSettingAPI(AccountWithTwoCreditCardAndNoDefault);

        // No need to login again, refresh page
		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Verify "Add another credit card" is present after clicking 'Choose a different card'
        await waitForSelectorAndText('.button.is-compact', 'Add another credit card' );

        // The index is based on the order in the settings' API. Inside the payment_methods prop.
        // This API end point is mocked, check fixtures/account_settings.js for ordering.
        const VISA_CARD_INDEX = 0;
        const MASTERCARD_INDEX = 1;

        const cardNumbers = await page.$$('.label-settings__card-number');
		const firstCardNumberElement = await cardNumbers[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardNumber = await firstCardNumberElement.jsonValue();
        await expect(firstCardNumber).toEqual('VISA ****5959');
		const secondCardNumberElement = await cardNumbers[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardNumber = await secondCardNumberElement.jsonValue();
        await expect(secondCardNumber).toEqual('MasterCard ****2862');

        const cardNames = await page.$$('.label-settings__card-name');
		const firstCardNameElement = await cardNames[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardName = await firstCardNameElement.jsonValue();
        await expect(firstCardName).toEqual('John Doe');
		const secondCardNameElement = await cardNames[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardName = await secondCardNameElement.jsonValue();
        await expect(secondCardName).toEqual('Jane Smith');

        const cardExpiryDates = await page.$$('.label-settings__card-date');
		const firstCardExpiryDateElement = await cardExpiryDates[VISA_CARD_INDEX].getProperty('innerText');
        const firstCardExpiryDate = await firstCardExpiryDateElement.jsonValue();
        await expect(firstCardExpiryDate).toEqual('Expires 2030-06-10');
		const secondCardExpiryDateElement = await cardExpiryDates[MASTERCARD_INDEX].getProperty('innerText');
        const secondCardExpiryDate = await secondCardExpiryDateElement.jsonValue();
        await expect(secondCardExpiryDate).toEqual('Expires 2025-12-31');

        // Verify that no default box is checked.
        const cardDefaultCheckbox = await page.$$('.label-settings__card-checkbox input');
		const firstCardDefaultCheckboxElement = await cardDefaultCheckbox[VISA_CARD_INDEX].getProperty('checked');
        const firstCardDefaultCheckbox = await firstCardDefaultCheckboxElement.jsonValue();
        await expect(firstCardDefaultCheckbox).toBeFalsy();
		const secondCardDefaultCheckboxElement = await cardDefaultCheckbox[MASTERCARD_INDEX].getProperty('checked');
        const secondCardDefaultCheckbox = await secondCardDefaultCheckboxElement.jsonValue();
        await expect(secondCardDefaultCheckbox).toBeFalsy();
    });

    it ('Should show the correct email receipts message', async () => {
        // This test continue to use the mocked account/settings from the above test, which relies on AccountWithTwoCreditCardAndNoDefault.
        await expect(page).toMatchElement('.label-settings__credit-card-description', { text: "Email the label purchase receipts to johndoe (johndoe) at john.doe@automattic.com" });
    });

    /**
     * Not really a test case. But needed to run this once, after all test case ran in this describe.
     */
    it('should turn off request interception after all credit card test ran', async () => {
        await page.setRequestInterception(false);
    });
});

describe( 'Packaging', () => {
	afterAll( async() => {
		await deleteAllPackages();
		await saveAndWait();
	} );

    let metricSystemValue = ''; // either "in" or "cm".

    it( 'Can add package' , async () => {
        const packageName = 'Package Box 5x5x5';

		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click to pop up modal
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button', { text: 'Add package' } );

        // Create a new package
        await waitForSelectorAndText( '.packages__add-edit-title.form-section-heading', 'Add a package' );

        // Set this once globally, all following test will use this metric
        const metricSystem = await page.$$('.text-control-with-affixes .text-control-with-affixes__suffix');
		const metricSystemElement = await metricSystem[0].getProperty('innerText');
        metricSystemValue = await metricSystemElement.jsonValue();

        await expect( page ).toFill( '.packages__properties-group #name', packageName );
        await expect( page ).toFill( '.form-dimensions-input__length input', '5' );
        await expect( page ).toFill( '.form-dimensions-input__width input', '5' );
        await expect( page ).toFill( '.form-dimensions-input__height input', '5' );
        await expect( page ).toFill( '.form-dimensions-input__box_weight input', '0.5' );
        await expect( page ).toClick( '.button.form-button.is-primary', { text: 'Add package' } );

        // Verify package shows up in list
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName } );
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "5 x 5 x 5 " + metricSystemValue });

        // Save package
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is saved.
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "5 x 5 x 5 " + metricSystemValue });
    });

    it( 'Can edit package' , async () => {
        const packageName = 'Package Box 10x10x10';

		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click "edit" once this session is loaded
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button.is-compact', { text: 'Edit' } );

        // Edit package
        await waitForSelectorAndText( '.packages__add-edit-title.form-section-heading', 'Edit package' );
        await expect( page ).toFill( '.packages__properties-group #name', packageName );
        await expect( page ).toFill( '.form-dimensions-input__length input', '10' );
        await expect( page ).toFill( '.form-dimensions-input__width input', '10' );
        await expect( page ).toFill( '.form-dimensions-input__height input', '10' );
        await expect( page ).toFill( '.form-dimensions-input__box_weight input', '0.8' );
        await expect( page ).toClick( '.button.form-button.is-primary', { text: 'Done' } )

        // Verify package shows up in list
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });

        // Save package
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is updated.
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });
    });

    it( 'Can delete package' , async () => {
        const packageName = 'Package Box 10x10x10';

		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, click "edit" once this session is loaded
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button.is-compact', { text: 'Edit' } );

        // Delete package
        await page.waitForSelector( '.packages__add-edit-title.form-section-heading', { text: 'Edit package' } );
        await expect( page ).toClick( '.button.packages__delete.is-scary.is-borderless', { text: 'Delete this package' } )

        // Verify package is no longer in the list.
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });

        // Save package
        await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
        await saveAndWait();

        // Refresh page and make sure it is deleted.
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).not.toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "10 x 10 x 10 " + metricSystemValue });
    });

    it ( 'Can select and add service package', async () => {
        const packageName = 'Flat Rate Envelope'; // This is provided in services_data_mock_with_card.json

		await StoreOwnerFlow.login();
        await StoreOwnerFlow.openSettings('shipping', 'woocommerce-services-settings');

        // Wait for "Add package" to finish loading, then click "Add package"
        await waitForSelectorAndText('.button:not([disabled])', 'Add package');
        await expect( page ).toClick( '.button', { text: 'Add package' } );

        // Click the "Service package" tab, pick and add a service package.
        await expect( page ).toClick( '.segmented-control__link.item-index-1' );
        await expect( page ).toClick( '.foldable-card__action.foldable-card__expand');
        await expect( page ).toClick( '.foldable-card__content :first-child .packages__packages-row-actions input');
        await expect( page ).toClick( '.button.form-button.is-primary', { text: 'Add package' } )

        // Verify the package was added to the list
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-details-name', { text: packageName });
        await expect(page).toMatchElement('.packages__packages-row .packages__packages-row-dimensions', { text: "12.5 x 9.5 x 0.5 " + metricSystemValue });
    });
});
