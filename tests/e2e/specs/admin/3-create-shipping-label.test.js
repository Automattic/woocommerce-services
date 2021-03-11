/**
 * @format
 */

/**
 * Internal dependencies
 */
import { clickReactButton } from '../../utils/index';
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
		// console.log( '# Cleaning up packages...' );
		await deleteAllPackages();
		await expect( page ).toClick( '.button.is-primary', { text: 'Save changes' } );
		await saveAndWait();
	} );

    it( 'should create a new shipping label', async () => {
        await withOrder( async ( order ) => {

			// console.log( '# Login in as Admin' );
            await StoreOwnerFlow.login();
			// console.log( `# Opening order page for order #${order.id}` );
            await StoreOwnerFlow.openExistingOrderPage( order.id );

            // Click on Create shipping label button
			// console.log( '# Creating new shipping label' );
			const newLabelButton = await page.$( '.shipping-label__new-label-button' );
			if( ! newLabelButton ) {
				throw new Error( 'No button to create new shipping label for order' );
			}
            await clickReactButton( '.shipping-label__new-label-button', { text: 'Create shipping label' } );
            await page.waitForSelector( '.dialog__content' );

			// console.log( '# Shipping label creation window open' );
			await page.waitForSelector(  '.address-step__suggestion-title', { text: 'Address entered' } );
			await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );

			const enterOriginPhone = await page.$( '.address-step__phone #origin_phone' );
			if ( enterOriginPhone ) {
				await expect( page ).toFill( '.address-step__phone #origin_phone', '11111111111' );
				await expect( page ).toClick( '.address-step__actions .form-button', { text: 'Use address as entered' } )

				await page.waitForSelector(  '.address-step__suggestion-title', { text: 'Address entered' } );
				await expect( page ).toClick( '.address-step__suggestion-title', { text: 'Address entered' } );
			}

			await expect( page ).toClick( '.button.is-primary', { text: 'Use selected address' } );

			// console.log( '# Shipping label selecting packaging' );
            const selectAPackageType = await page.$( '.packages-step__no-packages a', {
                text: 'Select a package type'
            } );
			const addPackage = await page.$( '.packages-step__package-items-header a', {
				text: 'Add package'
			} );
			const packageName = 'My Package';
			// console.log( '# Shipping label selecting package type' );
            if ( selectAPackageType ) {
				// console.log( '# No packages present, adding new package' );
                await selectAPackageType.click();
                await expect( page ).toFill( '#weight_default_box', '1' );

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', packageName );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="length"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="width"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="height"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="box_weight"]', '1' );

                await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );
				await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping packages have been saved.' } );

				// console.log( '# Shipping label package added' );
            } else if( addPackage ) {
				// console.log( '# Packages present, adding new package' );
				await addPackage.click();
                await expect( page ).toFill( '#weight_default_box', '1' );

                await page.waitForSelector( '.packages__add-edit-dialog' );

                await expect( page ).toFill( '.packages__add-edit-dialog #name', packageName );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="length"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="width"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="height"]', '1' );
                await expect( page ).toFill( '.packages__add-edit-dialog input[name="box_weight"]', '1' );

                await expect( page ).toClick( '.button.is-primary', { text: 'Add package' } );
				await page.waitForSelector( '.notice.is-success .notice__text', { text: 'Your shipping packages have been saved.' } );

				// console.log( '# Shipping label package added' );
			}

			await clickReactButton( '.button.is-primary', { text: 'Use these packages' } );

            await page.waitForSelector( '.is-success', {
                text: '1 item in 1 package: 2 kg total'
            } );

			// console.log( '# Shipping label selecting rate' );
            await page.waitForSelector( '#inspector-radio-control-0-0' );
            await expect( page ).toClick( '#inspector-radio-control-0-0' );

			// console.log( '# Shipping label purchasing...' );
            await expect( page ).toClick( '.button.is-primary', {
                text: 'Buy shipping label',
				timeout: 120000
            } );

			// console.log( '# Shipping label purchased!' );
            await page.waitForSelector( '.notice.is-success .notice__text', {
                text: 'Your shipping label was purchased successfully'
            } );
        } );
    } );
} );
