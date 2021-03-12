/**
 * External dependencies
 */
import React from 'react'
import { localize } from 'i18n-calypso'
import { Tooltip } from '@wordpress/components'

/**
 * Internal dependencies
 */
import Card from 'components/card'
import CarrierIcon from '../../components/carrier-icon'
import Gridicon from 'gridicons'

const Actions = localize( ( { translate } ) => {
	return (
		<>
			{ /* eslint-disable-next-line wpcalypso/jsx-classname-namespace */}
			<a className="button is-compact" href="admin.php?page=wc-settings&tab=shipping&section">{ translate( 'Add to shipping zones' ) }</a>
			<Tooltip
				position="top left"
				text={ translate( 'To be displayed at checkout, this carrier must be added as a shipping method to selected shipping zones' ) }
			>
				<div>
					<Gridicon icon="help-outline" size={ 18 }/>
				</div>
			</Tooltip>
		</>
	)
})

const CarrierDiscount = localize( ( {
	translate,
	name,
	amount
} ) => translate( 'Up to {{strong}}%(discountAmount)s{{/strong}} off on %(carrierName)s shipping labels', {
	args: {
		carrierName: name,
		discountAmount: amount,
	},
	components: {
		strong: <strong/>
	},
}))

const carrierItemMap = {
	'wc_services_usps': ( { translate } ) => (
		<div className="live-rates-carriers-list__element element-usps">
			<div className="live-rates-carriers-list__icon">
				<CarrierIcon carrier="usps" size={ 18 } />
			</div>
			<div className="live-rates-carriers-list__carrier">{ translate( 'USPS' ) }</div>
			<div className="live-rates-carriers-list__features">
				<ul>
					<li>{ translate( 'Ship with the largest delivery network in the United States' ) }</li>
					<li>
						<CarrierDiscount name={ translate( 'USPS' ) } amount="81%" />
					</li>
					<li>
						{ translate( 'Live rates for %(carrierName)s at checkout', {
							args: {
								carrierName: translate( 'USPS' ),
							},
						})}
					</li>
				</ul>
			</div>
			<div className="live-rates-carriers-list__actions"><Actions /></div>
		</div>

	),
	'wc_services_dhlexpress': ({ translate }) => (
		<div className="live-rates-carriers-list__element element-dhlexpress">
			<div className="live-rates-carriers-list__icon">
				<CarrierIcon carrier="dhlexpress" size={ 18 } />
			</div>
			<div className="live-rates-carriers-list__carrier">{ translate( 'DHL Express' ) }</div>
			<div className="live-rates-carriers-list__features">
				<ul>
					<li>{ translate( 'Express delivery from the experts in international shipping' ) }</li>
					<li><CarrierDiscount name={ translate( 'DHL Express' ) } amount="74%" /></li>
					<li>
						{ translate( 'Live rates for %(carrierName)s at checkout', {
							args: {
								carrierName: translate( 'DHL Express' ),
							},
						})}
					</li>
				</ul>
			</div>
			<div className="live-rates-carriers-list__actions"><Actions /></div>
		</div>
	),
}

const CarriersList = ({ translate, carrierIds }) => {
	return (
		<Card className="live-rates-carriers-list__wrapper">
			<div className="live-rates-carriers-list__heading">
				<div className="live-rates-carriers-list__icon"/>
				<div className="live-rates-carriers-list__carrier">{ translate( 'Carrier' ) }</div>
				<div className="live-rates-carriers-list__features">{ translate( 'Features' ) }</div>
				<div className="live-rates-carriers-list__actions"/>
			</div>
			{carrierIds.map( ( carrierId ) => {
				const CarrierView = carrierItemMap[ carrierId ]
				if ( ! CarrierView ) {
					return null
				}

				return (
					<CarrierView key={ carrierId } translate={ translate } />
				)
			})}
		</Card>
	)
}

export default localize( CarriersList )
