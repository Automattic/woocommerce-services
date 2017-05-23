/**
 * External dependencies
 */
import React, { Component } from 'react';
import classNames from 'classnames';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import FormFieldSet from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormSelect from 'components/forms/form-select';
import ShippingCard from './label-card';

class ShippingLabels extends Component {
	constructor( props ) {
		super( props );

		//TODO: use redux state with real data
		this.state = {
			visible: true,
			cards: [ {
				selected: true,
				type: 'VISA',
				digits: '1234',
				name: 'Name Surname',
				date: '12/19'
			}, {
				selected: false,
				type: 'MasterCard',
				digits: '5678',
				name: 'Name Surname',
				date: '01/20'
			} ]
		};
	}

	selectCard( index ) {
		const cards = this.state.cards.map( ( card ) => {
			return { ...card, selected: false };
		} );

		cards[ index ].selected = true;

		this.setState( { cards } );
	}

	render() {
		const { translate } = this.props;

		const onToggle = () => {
			this.setState( { visible: ! this.state.visible } );
		};

		const renderCard = ( card, index ) => {
			const onSelect = () => {
				this.selectCard( index );
			};

			return ( <ShippingCard
				key={ index }
				onSelect={ onSelect }
				{ ...card } /> );
		};

		return (
			<div className={ classNames( 'shipping__labels-container', { hidden: ! this.state.visible } ) }>
				<FormFieldSet>
					<FormLabel
						className="shipping__labels-paper-size"
						htmlFor="paper-size">
						{ translate( 'Paper size' ) }
					</FormLabel>
					<FormSelect name="paper-size">
						<option>{ translate( 'Letter' ) }</option>
						<option>{ translate( 'Legal' ) }</option>
						<option>{ translate( 'Label (4"x6")' ) }</option>
						<option>{ translate( 'A4' ) }</option>
					</FormSelect>
				</FormFieldSet>
				<FormFieldSet>
					<FormLabel
						className="shipping__cards-label">
						{ translate( 'Credit card' ) }
					</FormLabel>
					<p className="shipping__credit-card-description">
						{ translate( 'Use your credit card on file to pay for the labels you print or add a new one.' ) }
					</p>
					{ this.state.cards.map( renderCard ) }
					<Button href="https://wordpress.com/me/billing" target="_blank" compact>{ translate( 'Add another credit card' ) }</Button>
				</FormFieldSet>
			</div>
		);
	}
}

export default localize( ShippingLabels );
