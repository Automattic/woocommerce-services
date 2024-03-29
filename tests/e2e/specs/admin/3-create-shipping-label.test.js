/**
 * @format
 */
/* eslint no-console: [ "error", { "allow": [ "log" ] } ] */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { withOrder } from '../../fixtures/index';
import { deleteAllPackages, saveAndWait } from '../../utils/components';

describe( 'Create shipping label', () => {
    beforeAll( async () => {
        page.setDefaultTimeout( 0 );
        page.setDefaultNavigationTimeout( 0 );
        page.on( "dialog", ( dialog ) => {
            console.log( `accepting dialog: ${ dialog.message() }` );
            dialog.accept();
        } );
    } );

	afterAll( async () => {
		await deleteAllPackages();
		await saveAndWait();
	} );

    it( 'should create a new shipping label', async () => {
        await withOrder( async ( order ) => {

            await StoreOwnerFlow.login();
            await StoreOwnerFlow.openExistingOrderPage( order.id );

            // Click on Create shipping label button
            await expect( page ).toClick( '.shipping-label__new-label-button', { text: 'Create shipping label' } );
            await page.waitForSelector( '.label-purchase-modal__content' );

			await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );

			const enterOriginPhone = await page.$( '.address-step__phone input' );
			if ( enterOriginPhone ) {
				await expect( page ).toFill( '.address-step__phone input', '11111111111' );

				await expect( page ).toClick( '.address-step__actions .form-button', { text: 'Use address as entered' } )

				await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );
			}

			await expect( page ).toClick( '.button.is-primary', { text: 'Use selected address' } );

			await expect( page ).toFill( '#weight_default_box', '1' );

            const selectAPackageType = await page.$( '.packages-step__no-packages a', {
                text: 'Select a package type'
            } );
			const addPackage = await page.$( '.packages-step__package-items-header a', {
				text: 'Add package'
			} );
			const packageName = 'My Package';
            if ( selectAPackageType ) {
                await selectAPackageType.click();

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', packageName );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="length"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="width"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="height"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="box_weight"]', '1' );

                await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );
				await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping packages have been saved.' } );

            } else if( addPackage ) {
				await addPackage.click();

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', packageName );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="length"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="width"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="height"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="box_weight"]', '1' );

                await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );
				await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping packages have been saved.' } );
			}

			await expect( page ).toClick( '.button.is-primary', { text: 'Use these packages' } );

            await page.waitForSelector( '.is-success', {
                text: '1 item in 1 package: 2 kg total'
            } );

            await expect( page ).toClick( '.rates-step__shipping-rate-container input[type="radio"]:first-child' );

            await expect( page ).toClick( '.button.is-primary', {
                text: 'Buy shipping label',
				timeout: 120000
            } );

            await page.waitForSelector( '.notice.is-success .notice__text', {
                text: 'Your shipping label was purchased successfully'
            } );
        } );
    } );
} );
