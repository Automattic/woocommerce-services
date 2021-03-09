/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { ExternalLink, Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getShippingLabel } from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import Gridicon from "gridicons";

export const TariffCodeTitle = localize( ( { translate } ) => (
	<span>
		{ translate( 'HS Tariff number' ) } (
		<ExternalLink
			href="https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-shipping/#section-29"
			target="_blank"
		>
			{ translate( 'more info' ) }
		</ExternalLink>
		)
	</span>
) );

export const OriginCountryTitle = localize( ( { translate } ) => (
	<span>
		{ translate( 'Origin country' ) }
		<Tooltip text={ translate( 'Country where the product was manufactured or assembled' ) }>
			<span>
				<Gridicon icon="info-outline" size={ 18 } />
			</span>
		</Tooltip>
	</span>
) );

const ItemRowHeader = ( { translate, weightUnit } ) => (
	<div className="customs-step__item-rows-header">
		<span className="customs-step__item-description-column">{ translate( 'Description' ) }</span>
		<span className="customs-step__item-code-column">{ <TariffCodeTitle /> }</span>
		<span className="customs-step__item-weight-column">
			{ translate( 'Weight (%s per unit)', { args: [ weightUnit ] } ) }
		</span>
		<span className="customs-step__item-value-column">{ translate( 'Value ($ per unit)' ) }</span>
		<span className="customs-step__item-country-column">{ <OriginCountryTitle /> }</span>
	</div>
);

ItemRowHeader.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	weightUnit: PropTypes.string.isRequired,
};

export default connect( ( state, { orderId, siteId } ) => ( {
	weightUnit: getShippingLabel( state, orderId, siteId ).storeOptions.weight_unit,
} ) )( localize( ItemRowHeader ) );
