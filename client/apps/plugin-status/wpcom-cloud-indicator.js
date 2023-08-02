/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react'
import { connect } from 'react-redux'
import { localize } from 'i18n-calypso'

/**
 * Internal dependencies
 */
import Indicator from './indicator'
import FormSettingExplanation from 'wcs-client/components/forms/form-setting-explanation'
import { disconnectWPCOMCloud } from './state/actions';

const WPCOMCloudIndicator = ( { translate, status, onConnectClick, onDisconnectClick } ) => {
	const [ isFetchingData, setIsFetchingData ] = useState( false );

	const handleConnectClick = useCallback(( event ) => {
		event.preventDefault();

		setIsFetchingData( true );
		onConnectClick().finally( () => setIsFetchingData( false ) );
	}, [ onConnectClick, setIsFetchingData ]);

	const handleDisconnectClick = useCallback(( event ) => {
		event.preventDefault();

		setIsFetchingData( true );
		onDisconnectClick().finally( () => setIsFetchingData( false ) );
	}, [ onDisconnectClick, setIsFetchingData ]);

	let indicatorState, indicatorMessage;

	switch ( status ) {
		case 'disconnected':
			indicatorState = 'error';
			indicatorMessage = translate( 'Your site is not connected to the WordPress.com cloud' );
			break;

		case 'staging':
			indicatorState = 'warning';
			indicatorMessage = translate( 'This is a staging site' );
			break;

		case 'connected':
			indicatorState = 'success';
			indicatorMessage = translate( 'Your site is connected to the WordPress.com cloud' );
			break;

		default:
			indicatorState = 'fetching';
			indicatorMessage = translate( 'Loading…' );
			break;
	}

	if ( isFetchingData ) {
		indicatorState = 'fetching';
	}

	return (
		<Indicator
			title={ translate( 'WordPress.com cloud' ) }
			state={ indicatorState }
			message={ indicatorMessage }
		>
			<FormSettingExplanation>
				{ 'disconnected' === status && (
					<>
						{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
						<a onClick={ handleConnectClick } href="#">Connect to use WooCommerce Shipping &amp; Tax</a>
					</>
				) }
				{ 'connected' === status && (
					<>
						{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
						<a onClick={ handleDisconnectClick } href="#">Disconnect to use a different account</a>
					</>
				) }
			</FormSettingExplanation>
		</Indicator>
	)
}

const mapStateToProps = ( state ) => (
	{
		status: state.status.health_items.wpcom_cloud ? state.status.health_items.wpcom_cloud.status : undefined,
	}
)

const mapDispatchToProps = {
	onConnectClick: () => Promise.resolve(),
	onDisconnectClick: disconnectWPCOMCloud,
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( WPCOMCloudIndicator ) )
