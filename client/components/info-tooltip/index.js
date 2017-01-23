import React, { PropTypes, Component } from 'react';

import Tooltip from 'components/tooltip';
import Gridicon from 'components/gridicon';

export default class InfoTooltip extends Component {
	static propTypes = {
		className: PropTypes.string,
		position: PropTypes.string,
		maxWidth: PropTypes.string,
	};

	static defaultProps = {
		position: 'top',
		maxWidth: 'auto',
	};

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
				style={ { cursor: 'help', color: 'black' } } >
				<Gridicon ref="icon" icon="info-outline" size={ 18 } />
				{ this.state.showTooltip &&
					<Tooltip
						className="wc-connect-popover"
						isVisible
						showOnMobile
						onClose={ this.closeTooltip }
						position={ this.props.position }
						context={ this.refs && this.refs.icon }>
						<div className="wc-connect-popover-contents"
							style={ { maxWidth: this.props.maxWidth } } >
							{ this.props.children }
						</div>
					</Tooltip>
				}
			</span>
		);
	}
}
