/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import ClipboardButton from 'components/forms/clipboard-button';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextarea from 'components/forms/form-textarea';
import FormSettingExplanation from 'wcs-client/components/forms/form-setting-explanation';
import notices from 'notices';

class LogView extends Component {
	onCopy = () => {
		const { translate } = this.props;
		notices.success( translate( 'Log tail copied to clipboard' ), { duration: 2000 } );
	}

	render() {
		const { key, title, tail, url, count, translate } = this.props;
		const id = `wcs-log-${ key }`;
		return (
			<FormFieldset>
				<FormLabel htmlFor={ id }>{ title }</FormLabel>
				<FormTextarea
					id={ id }
					name={ id }
					readOnly
					value={ tail }
				/>
				<FormSettingExplanation className="plugin-status__log-explanation">
					<span className="plugin-status__log-explanation-span">
						{ translate(
							'Last %s entry. {{a}}Show full log{{/a}}',
							'Last %s entries. {{a}}Show full log{{/a}}',
							{
								args: [ count ],
								count: count,
								components: { a: <a href={ url } /> },
							} )
						}
					</span>
					<ClipboardButton
						text={ tail }
						onCopy={ this.onCopy }
						compact>
						{ translate( 'Copy for support' ) }
					</ClipboardButton>
				</FormSettingExplanation>
			</FormFieldset>
		);
	}
}

LogView.propTypes = {
	title: PropTypes.string,
	logKey: PropTypes.string,
};

export default connect(
	( state, { logKey } ) => state.status.logs[ logKey ]
)( localize( LogView ) );
