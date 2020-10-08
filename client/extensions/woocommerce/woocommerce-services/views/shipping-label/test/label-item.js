/** @format */

/**
 * External dependencies
 */
import React from 'react';
import { shallow } from 'enzyme';
import { merge } from 'lodash';
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { LabelItem } from '../label-item.js';
import PopoverMenuItem from 'components/popover/menu-item';

function createLabelItemWrapper( props = {} ) {
	const defaults = {
		siteId: 1,
		orderId: 1,
		label: {
			serviceName: "test",
			labelIndex: 1,
			packageName: "test package",
			productNames: "test product",
			receiptId: 1,
			labelId: 1,
			createdDate: "",
			refundableAmount: 1,
			currency: "",
			showDetails: true,
			expiryDate: "",
			anonymized: false,
			usedDate: "",
			tracking: "tracking code 01",
			carrierId: 1,
		},
		isModal: false,
		openRefundDialog: () => {},
		openReprintDialog: () => {},
		openDetailsDialog: () => {},
		translate: translate,
	};

	props = merge( defaults, props );

	return shallow( <LabelItem { ...props } /> );
}

describe( 'Label item', () => {
	describe( 'with customs info', () => {
		const props = {
			label: {
				commercialInvoiceUrl: 'commercial-invoice.pdf'
			}
		};
		const wrapper = createLabelItemWrapper( props );
		const renderedPrintCustomsFormLink = wrapper.find('PopoverMenuItem[children="Print customs form"]');

		it( 'renders a link to print it', function () {
			expect( renderedPrintCustomsFormLink.length ).toBe( 1 );

			window.open = jest.fn();
			renderedPrintCustomsFormLink.simulate( 'click' );
			expect( window.open ).toHaveBeenCalledWith( 'commercial-invoice.pdf' );
		} );

	} );

	describe( 'without customs info', () => {
		const wrapper = createLabelItemWrapper();
		const renderedPrintCustomsFormLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Print customs form' === n.children().text();
		}  );

		it( 'doest not render a link to print it', function () {
			expect( renderedPrintCustomsFormLink.length ).toBe( 0 );
		} );

	} );
} );
