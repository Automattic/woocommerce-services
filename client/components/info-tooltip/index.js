/**
 * External dependencies
 */
import React, { PropTypes, Component } from 'react';
import Gridicon from 'gridicons';
import classNames from 'classnames';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import Tooltip from 'components/tooltip';

export default class InfoTooltip extends Component {
	static propTypes = {
		className: PropTypes.string,
		position: PropTypes.string,
		anchor: PropTypes.node,
		maxWidth: PropTypes.oneOfType( [
			PropTypes.string,
			PropTypes.number,
		] ),
	};

	static defaultProps = {
		position: 'top',
		maxWidth: 'auto',
	};

	constructor( props ) {
		super( props );

		this.openTooltip = _.debounce( this.openTooltip.bind( this ), 100 );
		this.closeTooltip = _.debounce( this.closeTooltip.bind( this ), 100 );

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
		const anchor = this.props.anchor || <Gridicon icon="info-outline" size={ 18 } />;

		return (
			<span
				onMouseOver={ this.openTooltip }
				onMouseOut={ this.closeTooltip }
				className={ classNames( 'info-tooltip', this.props.className ) } >
				<span ref="anchor">{ anchor }</span>
				{ this.state.showTooltip &&
					<Tooltip
						className="info-tooltip__container wcc-root"
						isVisible
						showOnMobile
						onClose={ this.closeTooltip }
						position={ this.props.position }
						context={ this.refs && this.refs.anchor }>
						<div className="info-tooltip__contents"
							style={ { maxWidth: this.props.maxWidth } } >
							{ this.props.children }
						</div>
					</Tooltip>
				}
			</span>
		);
	}
}
