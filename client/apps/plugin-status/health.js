/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Indicator from './indicator';
import WooCommerceServicesIndicator from './woocommerce-services-indicator';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';

const HealthView = ( { translate, healthItems } ) => {
	return (
		<SettingsGroupCard heading={translate('Health', {
			context: 'This section displays the overall health of WooCommerce Shipping & Tax and the things it depends on',
		})}>
			<Indicator
				title={translate('WooCommerce')}
				message={healthItems.woocommerce.message}
				state={healthItems.woocommerce.state}
			/>
			<Indicator
				title={translate('Jetpack')}
				state={healthItems.jetpack.state}
				message={healthItems.jetpack.message}/>
			<WooCommerceServicesIndicator />
		</SettingsGroupCard>

	);
};

export default connect(
	( state ) => ( {
		healthItems: state.status.health_items,
	} )
)( localize( HealthView ) );
