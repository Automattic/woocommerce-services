/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import FormSettingExplanation from 'wcs-client/components/forms/form-setting-explanation';
import Indicator from './indicator';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';

const ServicesStatusView = ( { translate, moment, services } ) => {
	if ( false === services ) {
		return null;
	}

	const renderDescription = ( { timestamp, url } ) => {
		if ( timestamp > 0 ) {
			return (
				<FormSettingExplanation>
					{ translate( 'Request was made %s - check logs below or {{a}}edit service settings{{/a}}', {
						args: moment( timestamp * 1000 ).fromNow(),
						components: { a: <a href={ url } /> },
					} ) }
				</FormSettingExplanation>
			);
		}

		return (
			<FormSettingExplanation>
				<a href={ url }>{ translate( 'Edit service settings' ) }</a>
			</FormSettingExplanation>
		);
	};

	const renderIndicator = ( serviceItem, index ) => {
		return (
			<Indicator
				key={ index }
				title={ serviceItem.title }
				subtitle={ serviceItem.subtitle }
				state={ serviceItem.state }
				message={ serviceItem.message }>
				{ renderDescription( serviceItem ) }
			</Indicator>
		);
	};

	const renderContent = () => {
		if ( ! services.length ) {
			return (
				<FormSettingExplanation>
					{ translate( 'No services configured. {{a}}Add a shipping service{{/a}}', {
						components: {
							a: <a href="admin.php?page=wc-settings&tab=shipping" />,
						},
					} ) }
				</FormSettingExplanation>
			);
		}

		return services.map( renderIndicator );
	};

	return (
		<SettingsGroupCard heading={ translate( 'Services' ) }>
			{ renderContent() }
		</SettingsGroupCard>
	);
};

export default connect(
	( state ) => ( {
		services: state.status.services,
	} )
)( localize( ServicesStatusView ) );
