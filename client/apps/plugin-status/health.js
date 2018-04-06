/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import Indicator from './indicator';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';
import { checkRestApi } from './state/actions';

class HealthView extends Component {
	componentDidMount() {
		this.props.checkRestApi();
	}

	render() {
		const { translate, moment, healthItems } = this.props;

		const heading = translate( 'Health', {
			context: 'This section displays the overall health of WooCommerce Services and the things it depends on',
		} );

		const renderTimestamp = ( { timestamp } ) => {
			const refreshUrl = 'admin.php?page=wc-status&tab=connect&refresh=true';

			if ( ! timestamp ) {
				return (
					<FormSettingExplanation>
						<a href={ refreshUrl }>{ translate( 'Refresh' ) }</a>
					</FormSettingExplanation>
				);
			}

			return (
				<FormSettingExplanation>
					{ translate( 'Last updated %s. {{a}}Refresh{{/a}}', {
						args: moment( timestamp * 1000 ).fromNow(),
						components: { a: <a href={ refreshUrl } /> },
					} ) }
				</FormSettingExplanation>
			);
		};

		const renderIndicator = ( title, healthItem, showTimestamp ) => {
			return (
				<Indicator
					title={ title }
					state={ healthItem.state }
					message={ healthItem.message }>
					{ showTimestamp ? renderTimestamp( healthItem ) : null }
				</Indicator>
			);
		};

		return (
			<SettingsGroupCard heading={ heading }>
				{ renderIndicator( translate( 'WooCommerce' ), healthItems.woocommerce, false ) }
				{ renderIndicator( translate( 'Jetpack' ), healthItems.jetpack, false ) }
				{ renderIndicator( translate( 'WooCommerce Services Data' ), healthItems.woocommerce_services, true ) }
				{ healthItems.rest_api && renderIndicator( translate( 'REST API' ), healthItems.rest_api, false ) }
			</SettingsGroupCard>
		);
	}
}

export default connect(
	( state ) => ( {
		healthItems: state.status.health_items,
	} ),
	( dispatch ) => bindActionCreators( { checkRestApi }, dispatch ),
)( localize( HealthView ) );
