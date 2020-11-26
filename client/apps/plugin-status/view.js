/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import GlobalNotices from 'components/global-notices';
import HealthView from './health';
import LogView from './log';
import notices from 'notices';
import ServicesStatusView from './services';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';
import Toggle from 'components/toggle';
import {
	toggleLogging,
	toggleDebug,
	save,
} from './state/actions';

const StatusView = ( { actions, loggingEnabled, debugEnabled, translate } ) => {
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<HealthView />
			<ServicesStatusView />
			<SettingsGroupCard heading={ translate( 'Debug' ) }>
			<Toggle
					id="wcs-toggle-debug"
					checked={ debugEnabled }
					title={ translate( 'Debug' ) }
					description={ translate( 'Display troubleshooting information on the Cart and Checkout pages.' ) }
					trueText={ translate( 'Enabled' ) }
					falseText={ translate( 'Disabled' ) }
					saveOnToggle={ true }
					saveForm={ actions.save }
					updateValue={ actions.toggleDebug }
				/>
				<Toggle
					id="wcs-toggle-logging"
					checked={ loggingEnabled }
					title={ translate( 'Logging' ) }
					description={ translate( 'Write diagnostic messages to log files. Helpful when contacting support.' ) }
					trueText={ translate( 'Enabled' ) }
					falseText={ translate( 'Disabled' ) }
					saveOnToggle={ true }
					saveForm={ actions.save }
					updateValue={ actions.toggleLogging }
				/>
				<LogView
					logKey="shipping"
					title={ translate( 'Shipping Log' ) } />
				<LogView
					logKey="taxes"
					title={ translate( 'Taxes Log' ) } />
				<LogView
					logKey="other"
					title={ translate( 'Other Log' ) } />
			</SettingsGroupCard>
			<SettingsGroupCard heading={ translate( 'Support' ) }>
				<FormFieldset>
					<FormLegend>{ translate( 'Need help?' ) }</FormLegend>
					<p className="plugin-status__help-description">
						{ translate( 'Our team is here for you. View our {{docsA}}support docs{{/docsA}} ' +
						'or {{ticketA}}open a support ticket{{/ticketA}}.', {
							components: {
								docsA: <a
									href="https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/"
									target="_blank"
									rel="noopener noreferrer" />,
								ticketA: <a
									href="https://woocommerce.com/my-account/create-a-ticket/"
									target="_blank"
									rel="noopener noreferrer" />,
							},
						} ) }
					</p>
				</FormFieldset>
			</SettingsGroupCard>
		</div>
	);
};

export default connect(
	( state ) => ( {
		loggingEnabled: Boolean( state.status.logging_enabled ),
		debugEnabled: Boolean( state.status.debug_enabled ),
	} ),
	( dispatch ) => ( {
		actions: bindActionCreators( {
			toggleLogging,
			toggleDebug,
			save,
		}, dispatch ),
	} )
)( localize( StatusView ) );
