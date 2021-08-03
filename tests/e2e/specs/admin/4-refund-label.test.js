/**
 * @format
 */
/* eslint no-console: [ "error", { "allow": [ "log" ] } ] */

/**
 * Internal dependencies
 */
import { clickReactButton } from '../../utils/index';
import { StoreOwnerFlow } from '../../utils/flows';
import { withExpiredShippingLabelAndOrder, withShippingLabelAndOrder } from '../../fixtures/index';

describe.skip( 'Refund shipping label', () => {
	beforeAll( async () => {
		page.setDefaultTimeout( 0 );
		page.setDefaultNavigationTimeout( 0 );
		page.on( "dialog", ( dialog ) => {
			console.log( `accepting dialog: ${ dialog.message() }` );
			dialog.accept();
		} );
	} );

	beforeEach( async() => {
		await StoreOwnerFlow.login();
	} );

	it( "works if the label hasn't been used", async () => {
		await withShippingLabelAndOrder( async ( { order } ) => {
			await StoreOwnerFlow.openExistingOrderPage( order.id );

			await clickReactButton( '.ellipsis-menu > .button' );
			await clickReactButton( 'div > .popover > .popover__inner > .popover__menu > .popover__menu-item:nth-child(3)' );
			await clickReactButton( '.label-refund-modal > .dialog__action-buttons > .button:nth-child(2)' );

			await page.waitForSelector( '.notice.is-success .notice__text', {
				text: 'The refund request has been sent successfully.'
			} )

			// opening purchase modal again after refund
			const newLabelButton = await page.$( '.shipping-label__new-label-button' );
			if( ! newLabelButton ) {
				throw new Error( 'No button to create new shipping label for order' );
			}

			await clickReactButton( '.shipping-label__new-label-button' );

			await clickReactButton( '#inspector-radio-control-0-0' );

			await expect( page ).toClick( '.button.is-primary', {
				text: 'Buy shipping label',
				timeout: 120000
			} );

			await page.waitForSelector( '.notice.is-success .notice__text', {
				text: 'Your shipping label was purchased successfully'
			} );
		});

	} );

	it( "doesn't work for expired labels", async () => {
		await withExpiredShippingLabelAndOrder( async ( { order } ) => {
			await StoreOwnerFlow.openExistingOrderPage( order.id );

			await clickReactButton( '.ellipsis-menu > .button' );
			await expect( page ).toMatchElement( '.ellipsis-menu__menu .shipping-label__item-menu-reprint-expired' );
		});
	} );
} );
