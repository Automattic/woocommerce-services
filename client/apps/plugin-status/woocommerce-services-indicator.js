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
import FormSettingExplanation from 'components/forms/form-setting-explanation'
import { refreshServiceData } from './state/actions';

const WooCommerceServicesIndicator = ({ translate, moment, status, onRefreshClick }) => {
	const [isFetchingData, setIsFetchingData] = useState(false);
	const handleRefreshClick = useCallback((event) => {
		event.preventDefault();

		setIsFetchingData(true);
		onRefreshClick().finally(() => setIsFetchingData(false))
	}, [onRefreshClick, setIsFetchingData]);

	const currentTimestamp = Date.now() / 1000
	let indicatorState, indicatorMessage
	if (!status.has_service_schemas) {
		indicatorState = 'error'
		indicatorMessage = translate('No service data available')
	} else if (!status.timestamp) {
		indicatorState = 'warning'
		indicatorMessage = translate('Service data found, but may be out of date')
	} else if (status.timestamp < currentTimestamp - status.error_threshold) {
		indicatorState = 'error'
		indicatorMessage = translate('Service data was found, but is more than three days old')
	} else if (status.timestamp < currentTimestamp - status.warning_threshold) {
		indicatorState = 'warning'
		indicatorMessage = translate('Service data was found, but is more than one day old')
	} else {
		indicatorState = 'success'
		indicatorMessage = translate('Service data is up-to-date')
	}

	if( isFetchingData ) {
		indicatorState = 'fetching';
	}

	return (
		<Indicator
			title={translate('WooCommerce Shipping & Tax Data')}
			state={indicatorState}
			message={indicatorMessage}
		>
			<FormSettingExplanation>
				{status.timestamp && status.has_service_schemas ? translate('Last updated %s.', {
					args: moment(status.timestamp * 1000).fromNow(),
				}) : null}{' '}
				{/* eslint-disable-next-line jsx-a11y/anchor-is-valid */}
				<a onClick={handleRefreshClick} href="#">{translate('Refresh')}</a>
			</FormSettingExplanation>
		</Indicator>
	)
}

const mapStateToProps = (state) => (
	{
		status: state.status.health_items.woocommerce_services,
	}
)

const mapDispatchToProps = {
	onRefreshClick: refreshServiceData,
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(localize(WooCommerceServicesIndicator))
