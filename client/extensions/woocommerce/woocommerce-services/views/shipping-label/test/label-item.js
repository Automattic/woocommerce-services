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
import { Tooltip } from '@wordpress/components';

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
			carrierId: "",
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

		it( 'does not render a link to print it', function () {
			expect( renderedPrintCustomsFormLink.length ).toBe( 0 );
		} );
	} );

	describe( 'with usps letter', () => {
		const props = {
			label: {
				isLetter: true,
				carrierId: 'usps',
			}
		}
		const wrapper = createLabelItemWrapper( props );
		const requestRefundLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Request refund' === n.children().text();
		}  );

		it( 'Request refund is disabled', function () {
			expect( requestRefundLink.length ).toBe( 0 );
		} );

		const tooltip = wrapper.findWhere( ( n ) => {
			return n.is( Tooltip );
		}  );

		it( 'Tooltip message is displayed', function () {
			expect( tooltip.props().text ).toEqual('USPS letters are not eligible for refund.');
		} );
	} );

	describe( 'with usps package', () => {
		const props = {
			label: {
				isLetter: false,
				carrierId: 'usps',
			}
		}
		const wrapper = createLabelItemWrapper( props );
		const requestRefundLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Request refund' === n.children().text();
		}  );

		it( 'Request refund is not disabled', function () {
			expect( requestRefundLink.length ).toBe( 1 );
		} );

		const requestShipmentLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Schedule a pickup' === n.children().text();
		})

		it( 'Request shipment pickup is available', function () {
			expect( requestShipmentLink.length ).toBe( 1 );
		} );

	} );

	describe( 'with DHL package', () => {
		const props = {
			label: {
				isLetter: false,
				carrierId: 'dhlexpress',
			}
		}
		const wrapper = createLabelItemWrapper( props );
		const requestRefundLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Request refund' === n.children().text();
		}  );

		it( 'Request refund is not disabled', function () {
			expect( requestRefundLink.length ).toBe( 1 );
		} );

		const requestShipmentLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Schedule a pickup' === n.children().text();
		})

		it( 'Request shipment pickup is available', function () {
			expect( requestShipmentLink.length ).toBe( 1 );
		} );

	} );

	describe( 'with non usps carrier letter', () => {
		const props = {
			label: {
				isLetter: true,
				carrierId: 'canada_post',
			}
		}
		const wrapper = createLabelItemWrapper( props );
		const requestRefundLink = wrapper.findWhere( ( n ) => {
			return n.is( PopoverMenuItem ) && 'Request refund' === n.children().text();
		}  );

		it( 'Request refund is not disabled', function () {
			expect( requestRefundLink.length ).toBe( 1 );
		} );
	} );
} );
