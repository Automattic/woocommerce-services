import React, { PropTypes } from 'react';
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'lib/mixins/i18n';

const TRACKING_URL_MAP = {
	usps: 'https://tools.usps.com/go/TrackConfirmAction.action?tLabels=%s',
};

const TrackingLink = ( { provider_label_id, carrier_id } ) => {
	if ( ! provider_label_id ) {
		return <span>{ __( 'N/A' ) }</span>;
	}
	const url = TRACKING_URL_MAP[ carrier_id ];
	if ( ! url ) {
		return <span>{ provider_label_id }</span>;
	}
	return <a target="_blank" href={ sprintf( url, provider_label_id ) }>{ provider_label_id }</a>;
};

TrackingLink.propTypes = {
	provider_label_id: PropTypes.string,
	carrier_id: PropTypes.string,
};

export default TrackingLink;
