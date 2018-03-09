/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import Indicator from './indicator';
import SettingsGroupCard from 'components/settings-group-card';

const ServicesStatusView = ( { translate, moment, services } ) => {
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

	return (
		<SettingsGroupCard heading={ translate( 'Services' ) }>
			{ services.map( renderIndicator ) }
		</SettingsGroupCard>
	);
};

export default connect(
	( state ) => ( {
		services: state.status.services,
	} )
)( localize( ServicesStatusView ) );
