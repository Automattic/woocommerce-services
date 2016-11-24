import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import formatDate from 'lib/utils/format-date';
import timeAgo from 'lib/utils/time-ago';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';

const LabelHistory = ( { labelsHistory } ) => {
	const renderUserName = ( entry ) => {
		if ( entry.user_name ) {
			return <a href={ entry.user_url }>{ entry.user_name }</a>;
		}

		return <span>{ __( 'User' ) }</span>;
	};

	const renderHistoryEntry = ( entry, index ) => {
		const entryMoment = timeAgo( entry.time );
		const labelIds = entry.label_ids;
		const isOneLabel = 1 === labelIds.length;
		let actionDesc = null;
		let icon = 'help-outline';

		switch ( entry.entry_type ) {
			case 'purchase':
				actionDesc = isOneLabel ? __( 'purchased Label #%s' ) : __( 'purchased labels: %s' );
				icon = 'plus';
				break;
			case 'refund_success':
				actionDesc = isOneLabel ? __( 'successfully requested a refund of Label #%s' ) : __( 'successfully requested a refund of labels: %s' );
				icon = 'refund';
				break;
			case 'refund_error':
				actionDesc = isOneLabel ? __( 'unsuccessfully requested a refund of Label #%s' ) : __( 'unsuccessfully requested a refund of labels: %s' );
				icon = 'refund';
				break;
			case 'reprint':
				actionDesc = isOneLabel ? __( 'reprinted Label #%s' ) : __( 'reprinted labels: %s' );
				icon = 'print';
				break;
			default:
				actionDesc = __( 'performed an unknown action' );
				break;
		}

		actionDesc = sprintf( actionDesc, labelIds.join( ', ' ) );

		return (
			<div key={ index } className="wcc-metabox-label-item">
				<div>
					<p><span title={ formatDate( entry.time ) }>{ entryMoment }</span></p>
					<p>
						{ renderUserName( entry ) } { actionDesc }
					</p>
				</div>
				<div>
					<Gridicon icon={ icon } size={ 18 } />
				</div>
			</div>
		);
	};

	return (
		<div className="wcc-metabox-shipping-label-history-container">
			{ labelsHistory.map( renderHistoryEntry ) }
		</div>
	);
};

LabelHistory.propTypes = {
	labelsHistory: PropTypes.array.isRequired,
};

export default LabelHistory;
