/**
 * External dependencies
 */
import React, { Component } from 'react';
// import PropTypes from 'prop-types';
import _ from 'lodash';
// import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FormLegend from 'components/forms/form-legend';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import TokenField from 'components/token-field';

class ShippingClasses extends Component {
	constructor( props ) {
		super( props );

		const values = _.map( props.shippingClasses, 'value' );
		const names = _.map( props.shippingClasses, 'name' );

		this.shippingClassNames = names;
		this.shippingClassValueMap = _.zipObject( _.map( values, ( value ) => '_' + value ), names );
		this.shippingClassNameMap = _.zipObject( names, values );
		console.log( this.shippingClassValueMap );
		console.log( this.shippingClassNameMap );
	}
	/**
	 *
	 * IDEA !!!!
	 *
	 * in constructor, create map of { id => name } from suggestions + value
	 *
	 * use for display transform
	 *
	 */

	displayTransform = ( tokenValue ) => {
		console.log( 'displayTransform', tokenValue, '->', this.shippingClassValueMap[ '_' + tokenValue ] || tokenValue );
		return this.shippingClassValueMap[ '_' + tokenValue ] || tokenValue;
	};

	saveTransform = ( tokenName ) => {
		console.log( 'saveTransform', tokenName, '->', this.shippingClassNameMap[ tokenName ] || tokenName );
		return this.shippingClassNameMap[ tokenName ] || tokenName;
	};

	render() {
		const {
			title,
			description,
			value,
			updateValue,
		} = this.props;

		return (
			<div>
				<FormLegend>{ title }</FormLegend>
				<TokenField
					suggestions={ this.shippingClassNames }
					value={ value }
					displayTransform={ this.displayTransform }
					saveTransform={ this.saveTransform }
					onChange={ updateValue }
				/>
				<FormSettingExplanation>{ description }</FormSettingExplanation>
			</div>
		);
	}
}

export default ShippingClasses;
