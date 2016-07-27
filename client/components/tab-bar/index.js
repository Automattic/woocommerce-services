import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';

const TabBar = ( { layout, currentStep, onTabClick } ) => {
	const renderTab = ( label, index ) => {
		let labelElement;
		let className = '';
		if ( index === currentStep ) {
			labelElement = <span>{ label }</span>;
			className = 'is-active';
		} else if ( index < currentStep ) {
			labelElement = (
				<div>
					<Gridicon icon="checkmark-circle"/>
					<a onClick={ () => onTabClick( index ) }>{ label }</a>
				</div>
			);
			className = 'is-completed';
		} else {
			labelElement = <span>{ label }</span>;
		}
		return (
			<li key={ index } className={ className }>
				{ labelElement }
			</li>
		);
	};

	return (
		<ul className="wcc-dialog-tabs">
			{ layout.map( ( step, index ) => renderTab( step.tab_title, index ) ) }
		</ul>
	);
};

TabBar.propTypes = {
	layout: PropTypes.array.isRequired,
	currentStep: PropTypes.number.isRequired,
	onTabClick: PropTypes.func.isRequired,
};

export default TabBar;
