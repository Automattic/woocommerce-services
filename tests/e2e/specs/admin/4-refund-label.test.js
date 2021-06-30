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

describe( 'Refund shipping label', () => {

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
			await clickReactButton( '.ReactModalPortal > .ReactModal__Overlay > .ReactModal__Content > .dialog__action-buttons > .button:nth-child(2)' );

			await clickReactButton( '#postbox-container-2 > #normal-sortables > #woocommerce-order-label > .inside > .wcc-root' );
			const newLabelButton = await page.$( '.wcc-root > .shipping-label__container > div > div > .button' );
			if( ! newLabelButton ) {
				throw new Error( 'No button to create new shipping label for order' );
			}
			await clickReactButton( '.wcc-root > .shipping-label__container > div > div > .button' );
			await clickReactButton( '.label-purchase-modal__body > .label-purchase-modal__sidebar > .label-purchase-modal__purchase-container > .purchase-section > .button' );

			await page.waitForSelector( '.notice.is-success .notice__text', {
				text: 'The refund request has been sent successfully.'
			} )
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
