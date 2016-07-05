import React, { PropTypes } from 'react';

const Tabs = ( { layout, currentStep, onTabClick } ) => {
	const renderTab = ( label, index ) => {
		if ( index === currentStep ) {
			return <b>{ label }</b>;
		} else if ( index < currentStep ) {
			return <a onClick={ () => onTabClick( index ) }>{ label }</a>;
		}
		return <span>{ label }</span>;
	};

	return (
		<ul>
			{ layout.map( ( step, index ) => (
				<li key={ index }>
					{ renderTab( step.tab_title, index ) }
				</li>
			) ) }
		</ul>
	);
};

Tabs.propTypes = {
	layout: PropTypes.array.isRequired,
	currentStep: PropTypes.number.isRequired,
	onTabClick: PropTypes.func.isRequired,
};

export default Tabs;
