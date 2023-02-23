/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { Button, ExternalLink, Popover, Tooltip } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getShippingLabel } from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import Gridicon from "gridicons";

const EUCustomsSpecificityRequirementPopover = localize( ( { togglePopoverVisible, translate } ) => (
	<Popover onFocusOutside={ togglePopoverVisible }>
		<p>
			{ translate( 'When shipping to countries that follow European Union (EU) customs rules, you must provide a clear, specific description on every item.' ) }
		</p>
		<p>
			{ translate( 'For example, if you are sending clothing, you must indicate what type of clothing (e.g. men\'s shirts, girl\'s vest, boy\'s jacket) for the description to be acceptable.' ) }
		</p>
		<p>
			{ translate( 'Otherwise, shipments may be delayed or interrupted at customs.' ) }
		</p>
		<p>
			<ExternalLink
				href="https://www.usps.com/international/new-eu-customs-rules.htm"
			>
				{ translate( 'Learn more about customs rules' ) }
			</ExternalLink>
		</p>
	</Popover>
) );

export const DescriptionTitle = localize( ( { translate } ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const togglePopoverVisible = () => {
		setIsPopoverVisible( ( state ) => ! state );
	};

	return <span>
		{ translate( 'Description' ) }
		<Button onClick={ togglePopoverVisible }>
			<span>
				<Gridicon icon="info-outline" size={ 18 } />
			</span>
		</Button>
		<span className="popover-container">
			{ isPopoverVisible && <EUCustomsSpecificityRequirementPopover togglePopoverVisible={ togglePopoverVisible } /> }
		</span>
	</span>;
} );

export const TariffCodeTitle = localize( ( { translate } ) => (
	<span>
		{ translate( 'HS Tariff number' ) } (
		<ExternalLink
			href="https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-shipping/#section-29"
		>
			{ translate( 'more info' ) }
		</ExternalLink>
		)
	</span>
) );

export const OriginCountryTitle = localize( ( { translate } ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const togglePopoverVisible = () => {
		setIsPopoverVisible( ( state ) => ! state );
	};

	return <span>
		{ translate( 'Origin country' ) }
		<Button onClick={ togglePopoverVisible }>
				<span>
					<Gridicon icon="info-outline" size={ 18 } />
				</span>
			</Button>
			<span className="popover-container">
				{ isPopoverVisible && <Popover onFocusOutside={ togglePopoverVisible }>
					<p>{ translate( 'Country where the product was manufactured or assembled.' ) }</p>
				</Popover> }
			</span>
		</span>;
} );

const ItemRowHeader = ( { translate, weightUnit } ) => (
	<div className="customs-step__item-rows-header">
		<div className="customs-step__item-description-column"><DescriptionTitle /></div>
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
