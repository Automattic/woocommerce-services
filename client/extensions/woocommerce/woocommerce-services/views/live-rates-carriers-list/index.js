/**
 * External dependencies
 */
import React from 'react'
import { localize } from 'i18n-calypso'

/**
 * Internal dependencies
 */
import ExtendedHeader from 'woocommerce/components/extended-header'
import CarriersList from './carriers-list'

const supportedCarrierIds = ['wc_services_usps', 'wc_services_dhlexpress'];

const LiveRatesCarriersList = ({ translate, carrierIds }) => {
	if (carrierIds.some(carrierId => supportedCarrierIds.includes(carrierId)) === false) {
		return null
	}

	return (
		<div>
			<ExtendedHeader
				label={translate('Live rates at checkout')}
				description={translate('Show live rates directly on your store - never under or overcharge for shipping again')}
			/>
			<CarriersList carrierIds={carrierIds} />
		</div>
	)
}

export default localize(LiveRatesCarriersList)
