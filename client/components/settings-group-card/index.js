/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';

const SettingsGroupCard = ( { heading, children } ) => {
	return (
		<CompactCard className="settings-group-card">
			{ heading && <FormSectionHeading className="settings-group-card__heading">{ heading }</FormSectionHeading> }
			<div className={ classnames( 'settings-group-card__content', { 'is-full-width': ! heading } ) } >
				{ children }
			</div>
		</CompactCard>
	);
};

SettingsGroupCard.propTypes = {
	heading: PropTypes.node,
};

export default SettingsGroupCard;
