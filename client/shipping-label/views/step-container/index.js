import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';
import Spinner from 'components/spinner';
import Card from 'components/card';
import { translate as __ } from 'lib/mixins/i18n';
import classNames from 'classnames';

const ExpandButton = () => (
	<button className="foldable-card__action foldable-card__expand" type="button">
		<span className="screen-reader-text">{ __( 'Expand' ) }</span>
		<Gridicon icon="chevron-down" size={ 24 } />
	</button>
);

const StepContainer = ( { isSuccess, isWarning, isError, isProgress, title, summary, children, expanded, toggleStep } ) => {
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

	const renderHeader = () => {
		return (
			<div className="foldable-card__header is-clickable has-border" onClick={ toggleStep }>
				<span className="foldable-card__main">{ header } </span>
				<span className="foldable-card__secondary">
					<span className="foldable-card__summary">{ summary } </span>
					<span className="foldable-card__summary_expanded">{ summary } </span>
					<div className="foldable-card__action">
						<ExpandButton/>
					</div>
				</span>
			</div>
		);
	};

	const renderContent = () => {
		return (
			<div className="foldable-card__content">
				{ children }
			</div>
		);
	};

	return (
		<Card className={ classNames( 'foldable-card', 'has-expanded-summary', { 'is-expanded': expanded } ) }>
			{ renderHeader() }
			{ expanded && renderContent() }
		</Card>
	);
};

StepContainer.propTypes = {
	isSuccess: PropTypes.bool,
	isWarning: PropTypes.bool,
	isError: PropTypes.bool,
	isProgress: PropTypes.bool,
	title: PropTypes.string.isRequired,
	summary: PropTypes.string,
	expanded: PropTypes.bool,
};

export default StepContainer;
