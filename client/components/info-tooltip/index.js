import React, { Component } from 'react';

import Tooltip from 'components/tooltip';
import Gridicon from 'components/gridicon';

export default class InfoTooltip extends Component {
	constructor( props ) {
		super( props );

		this.openTooltip = this.openTooltip.bind( this );
		this.closeTooltip = this.closeTooltip.bind( this );

		this.state = {
			showTooltip: false,
		};
	}

	openTooltip() {
		this.setState( { showTooltip: true } );
	}

	closeTooltip() {
		this.setState( { showTooltip: false } );
	}

	render() {
		return (
			<span
				onMouseEnter={ this.openTooltip }
				onMouseLeave={ this.closeTooltip }
				className={ this.props.className }
				style={ { cursor: 'help' } } >
				<Gridicon ref="icon" icon="info-outline" size={ 18 } />
				{ this.state.showTooltip &&
					<Tooltip
						className="wc-connect-popover"
						isVisible
						onClose={ this.closeTooltip }
						position="top"
						context={ this.refs && this.refs.icon }>
						{ this.props.children }
					</Tooltip>
				}
			</span>
		);
	}
}
