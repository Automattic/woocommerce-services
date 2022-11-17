/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { uniq, map, find, filter } from 'lodash';
import { FormTokenField } from '@wordpress/components';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormSettingExplanation from 'components/forms/form-setting-explanation';

/*
* Style dependencies
*/
import './style.scss';

export default class ShippingClassesField extends React.Component {
	static propTypes = {
		id: PropTypes.string.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		value: PropTypes.array.isRequired,
		updateValue: PropTypes.func,
		options: PropTypes.array,
	};

	render() {
		const { title, description, value, placeholder, options } = this.props;

		// If there are no shipping classes, no input for them is needed.
		if ( false === options || 0 === options.length ) {
			return null;
		}

		return (
			<FormFieldset>
				<FormTokenField
					label={ title }
					placeholder={ placeholder }
					value={ this.prepareValueForTokenField( value ) }
					suggestions={ uniq( map( options, 'name' ) ) }
					onChange={ this.onChange }
					displayTransform={ this.transformForDisplay }
					className="shipping-classes-field__form-token"
				/>
				{ description && <FormSettingExplanation>{ description }</FormSettingExplanation> }
			</FormFieldset>
		);
	}

	prepareValueForTokenField = value => {
		return filter( map( value, this.getNameFromId ) );
	};

	getNameFromId = id => {
		const found = find( this.props.options, { id } );

		return found ? found.name : null;
	};

	getIdFromName = name => {
		const lowerCaseName = name.toLowerCase();

		const found = find( this.props.options, option => {
			return option.name.toLowerCase() === lowerCaseName;
		} );

		return found ? found.id : null;
	};

	transformForDisplay = token => {
		const option = find( this.props.options, { slug: token } );

		return option ? option.name : token;
	};

	onChange = strings => {
		const { updateValue } = this.props;

		const updatedValue = uniq( filter( map( strings, this.getIdFromName ) ) );

		updateValue( updatedValue );
	};
}
