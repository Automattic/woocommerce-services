/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
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
	toggleDebugging,
} from './state/actions';

const StatusView = ( { onLoggingToggle, onDebuggingToggle, isLoggingEnabled, isDebuggingEnabled, translate } ) => {
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<HealthView />
			<ServicesStatusView />
			<SettingsGroupCard heading={ translate( 'Debug' ) }>
			<Toggle
					id="wcs-toggle-debug"
					checked={ isDebuggingEnabled }
					title={ translate( 'Debug' ) }
					description={ translate( 'Display troubleshooting information on the Cart and Checkout pages.' ) }
					trueText={ translate( 'Enabled' ) }
					falseText={ translate( 'Disabled' ) }
					onUpdate={ onDebuggingToggle }
				/>
				<Toggle
					id="wcs-toggle-logging"
					checked={ isLoggingEnabled }
					title={ translate( 'Logging' ) }
					description={ translate( 'Write diagnostic messages to log files. Helpful when contacting support.' ) }
					trueText={ translate( 'Enabled' ) }
					falseText={ translate( 'Disabled' ) }
					onUpdate={ onLoggingToggle }
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

const mapStateToProps = ( state ) => ( {
	isLoggingEnabled: Boolean( state.status.logging_enabled ),
	isDebuggingEnabled: Boolean( state.status.debug_enabled ),
} );

const mapDispatchToProps = {
	onLoggingToggle: toggleLogging,
	onDebuggingToggle: toggleDebugging,
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( StatusView ) );
