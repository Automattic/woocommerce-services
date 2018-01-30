/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import Checkbox from 'components/checkbox';
import FormSelect from 'components/forms/form-select';
import NumberInput from 'components/number-field/number-input';

class ShippingServiceEntry extends Component {
	static propTypes = {
		service: PropTypes.shape( {
			id: PropTypes.string.isRequired,
			name: PropTypes.string.isRequired,
			enabled: PropTypes.bool,
			adjustment: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.number,
			] ),
			adjustment_type: PropTypes.string,
		} ),
		currencySymbol: PropTypes.string.isRequired,
		updateValue: PropTypes.func.isRequired,
		settingsKey: PropTypes.string.isRequired,
	};

	static defaultProps = {
		enabled: false,
		adjustment: 0,
		adjustment_type: 'flat',
	};

	onToggleEnabled = ( event ) => this.props.updateValue( 'enabled', event.target.checked );
	onUpdateAdjustment = ( event ) => this.props.updateValue( 'adjustment', event.target.value );
	onUpdateAdjustmentType = ( event ) => this.props.updateValue( 'adjustment_type', event.target.value );

	render() {
		const {
			currencySymbol,
			errors,
			service,
		} = this.props;

		const {
			enabled,
			name,
			adjustment,
			adjustment_type,
		} = service;

		const hasError = errors[ service.id ];

		return (
			<div className={ classNames( 'shipping-services__entry', { 'wcc-error': hasError } ) } >
				<label htmlFor={ service.id } className="shipping-services__entry-title">
					<Checkbox
						id={ service.id }
						checked={ enabled }
						onChange={ this.onToggleEnabled }
					/>
					<span>{ name }</span>
				</label>
				{ hasError ? <Gridicon icon="notice" /> : null }
				<NumberInput
					disabled={ ! enabled }
					value={ adjustment }
					onChange={ this.onUpdateAdjustment }
					isError={ hasError }
				/>
				<FormSelect
					disabled={ ! enabled }
					value={ adjustment_type }
					onChange={ this.onUpdateAdjustmentType }
				>
					<option value="flat">{ currencySymbol }</option>
					<option value="percentage">%</option>
				</FormSelect>
			</div>
		);
	}
}

export default ShippingServiceEntry;
