/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Indicator from './indicator';
import WooCommerceServicesIndicator from './woocommerce-services-indicator';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';
import FormSettingExplanation from 'components/forms/form-setting-explanation';

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
				message={healthItems.jetpack.message}
			/>
			<Indicator
				title={translate('Automated Taxes')}
				state={healthItems.automated_taxes.state}
				message={healthItems.automated_taxes.message}
			>
				<FormSettingExplanation>
					{healthItems.automated_taxes.show_settings_link && (
						<>
							<ExternalLink
								href="admin.php?page=wc-settings&tab=tax"
							>
								{translate('Go to the Tax settings')}
							</ExternalLink>
							<br/>
						</>
					)}
					<ExternalLink
						href="https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-tax/"
					>
						{translate('Automated taxes documentation')}
					</ExternalLink>
				</FormSettingExplanation>
			</Indicator>
			<WooCommerceServicesIndicator/>
		</SettingsGroupCard>

	);
};

export default connect(
	( state ) => ( {
		healthItems: state.status.health_items,
	} )
)( localize( HealthView ) );
