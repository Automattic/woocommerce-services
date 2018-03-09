/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormTextarea from 'components/forms/form-textarea';
import FormSettingExplanation from 'components/forms/form-setting-explanation';

const LogView = ( { key, title, tail, url, count, translate } ) => {
	const id = `wcs-log-${ key }`;
	return (
		<FormFieldset>
			<FormLabel htmlFor={ id }>{ title }</FormLabel>
			<FormTextarea
				id={ id }
				name={ id }
				readOnly={ true }
				value={ tail }
			/>
			<FormSettingExplanation>
				{ translate( 'Last %s entries. {{a}}Show full log{{/a}}', {
					args: count,
					components: { a: <a href={ url } /> },
				} ) }
			</FormSettingExplanation>
		</FormFieldset>
	);
};

LogView.propTypes = {
	title: PropTypes.string,
	logKey: PropTypes.string,
};

export default connect(
	( state, { logKey } ) => ( state.status.logs[ logKey ] )
)( localize( LogView ) );
