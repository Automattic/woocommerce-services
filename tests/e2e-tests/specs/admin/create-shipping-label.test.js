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

			console.log( '# Login in as Admin' );
            await StoreOwnerFlow.login();
			console.log( `# Opening order page for order #${order.id}` );
            await StoreOwnerFlow.openExistingOrderPage( order.id );

            // Click on Create shipping label button
			console.log( '# Creating new shipping label' );
            await clickReactButton( '.shipping-label__new-label-button', { text: 'Create shipping label' } );
            await page.waitForSelector( '.dialog__content' );

			console.log( '# Shipping label creation window open' );
			await page.waitForSelector( '.address-step__suggestion-title', { text: 'Address entered' } );
			await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );
            await expect( page ).toFill( '.address-step__company-phone #origin_phone', '11111111111' );
            await expect( page ).toClick( '.address-step__actions .form-button', { text: 'Use address as entered' } )

			await page.waitForSelector(  '.address-step__suggestion-title', { text: 'Address entered' } );
            await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );
			await expect( page ).toClick( '.button.is-primary', { text: 'Use selected address' } );

			console.log( '# Shipping label selecting packaging' );
            const selectAPackageType = await page.$( '.packages-step__no-packages a', {
                text: 'Select a package type'
            } );
			console.log( '# Shipping label selecting package type' );
            if ( selectAPackageType ) {
                await selectAPackageType.click();
                await expect( page ).toFill( '#weight_default_box', '1' );

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', 'My Package' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="length"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="width"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="height"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="box_weight"]', '1' );

                await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );

				console.log( '# Shipping label package added' );
            }
            await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping packages have been saved.' } );

			await clickReactButton( '.button.is-primary', { text: 'Use these packages' } )

            await page.waitForSelector( '.is-success', {
                text: '1 item in 1 package: 2 kg total'
            } );

			console.log( '# Shipping label selecting rate' );
            await page.waitForSelector( '#inspector-radio-control-0-0' );
            await expect( page ).toClick( '#inspector-radio-control-0-0' );

			console.log( '# Shipping label purchasing...' );
            await expect( page ).toClick( '.button.is-primary', {
                text: 'Buy shipping labels',
				timeout: 120000
            } );

			console.log( '# Shipping label purchased!' );
            await page.waitForSelector( '.notice.is-success .notice__text', {
                text: 'Your shipping label was purchased successfully'
            } )
        } );
    } );
} );
