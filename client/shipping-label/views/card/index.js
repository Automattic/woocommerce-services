import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import Spinner from 'components/spinner';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';

const ExpandButton = () => (
	<button className="foldable-card__action foldable-card__expand" type="button">
		<span className="screen-reader-text">{ __( 'Expand' ) }</span>
		<Gridicon icon="chevron-down" size={ 24 } />
	</button>
);

const Card = ( { isSuccess, isWarning, isError, isProgress, title, summary, children, expanded } ) => {
	const getIcon = () => {
		if ( isSuccess ) {
			return 'checkmark';
		}
		if ( isWarning ) {
			return 'notice-outline';
		}
		if ( isError ) {
			return 'notice-outline';
		}
		return '';
	};
	const className = classNames( {
		'is-success': isSuccess,
		'is-warning': isWarning,
		'is-error': isError,
		'is-progress': isProgress,
	} );

	summary = <span className={ className }>{ summary }</span>;
	const header = (
		<div>
			{ isProgress ? <Spinner size={ 24 } /> : <Gridicon icon={ getIcon() } className={ className } size={ 24 } /> }
			{ title }
		</div>
	);

	return (
		<FoldableCard
			header={ header }
			summary={ summary }
			expandedSummary={ summary }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ Boolean( expanded ) } >
			{ children }
		</FoldableCard>
	);
};

Card.propTypes = {
	isSuccess: PropTypes.bool,
	isWarning: PropTypes.bool,
	isError: PropTypes.bool,
	isProgress: PropTypes.bool,
	title: PropTypes.string.isRequired,
	summary: PropTypes.string,
	expanded: PropTypes.bool,
};

export default Card;
