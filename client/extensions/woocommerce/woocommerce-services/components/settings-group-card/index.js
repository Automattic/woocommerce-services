/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import FormSectionHeading from 'wcs-client/components/forms/form-section-heading';

const SettingsGroupCard = ( { heading, children } ) => {
	return (
		<Card className={ classnames( 'settings-group-card', 'card', 'is-compact' ) } >
			{ heading && (
				<FormSectionHeading className="settings-group-card__heading">
					{ heading }
				</FormSectionHeading>
			) }
			<div
				className={ classnames( 'settings-group-card__content', { 'is-full-width': ! heading } ) }
			>
				{ children }
			</div>
		</Card>
	);
};

SettingsGroupCard.propTypes = {
	heading: PropTypes.node,
};

export default SettingsGroupCard;

