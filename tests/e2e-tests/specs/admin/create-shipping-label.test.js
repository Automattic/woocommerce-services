/**
 * @format
 */

/**
 * Internal dependencies
 */
import { clickReactButton } from '../../utils/index';
import { StoreOwnerFlow } from '../../utils/flows';
import { withOrder } from '../../fixtures/index';

describe( 'Create shipping label', () => {
    beforeAll( async () => {
        page.setDefaultTimeout( 0 );
        page.setDefaultNavigationTimeout( 0 );
        page.on( "dialog", ( dialog ) => {
            console.log( `accepting dialog: ${ dialog.message() }` );
            dialog.accept();
        } );
    } );

    it( 'should create a new shipping label', async () => {
        await withOrder( async ( order ) => {


            await StoreOwnerFlow.login();
            await StoreOwnerFlow.openExistingOrderPage( order.id );

            // Click on Create shipping label button
            await clickReactButton( '.shipping-label__new-label-button' );
            await page.waitForSelector( '.dialog__content' );

			await page.waitForSelector( '.is-success', { text: 'NEW YORK, NY  10075' } );

			await page.waitForSelector(  '.address-step__suggestion-title', { text: 'Address entered' } );
            await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );
			await expect( page ).toClick( '.button.is-primary', {
				text: 'Use selected address'
			} );

            const selectAPackageType = await page.$( '.packages-step__no-packages a', {
                text: 'Select a package type'
            } );
            if ( selectAPackageType ) {
                await expect( page ).toClick( 'Select a package type' );
                await selectAPackageType.click();
                await expect( page ).toFill( '#weight_default_box', '1' );

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', 'My Package' );
                await expect( page ).toFill( '.packages__add-edit-dialog #length', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog #width', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog #height', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog #box_weight', '1' );

                await expect( page ).toClick( '.button.is-primary', {
                    text: 'Add package',
                } );

            }

            await expect( page ).toClick( '.button.is-primary', {
                text: 'Use these packages',
				timeout: 120000
            } );
            await page.waitForSelector( '.is-success', {
                text: '1 item in 1 package: 2 kg total'
            } );

            await page.waitForSelector( '#inspector-radio-control-0-0' );
            await expect( page ).toClick( '#inspector-radio-control-0-0' );

            await expect( page ).toClick( '.button.is-primary', {
                text: 'Buy shipping labels',
				timeout: 120000
            } );

            await page.waitForSelector( '.notice.is-success .notice__text', {
                text: 'Your shipping label was purchased successfully'
            } )
        } );
    } );
} );
