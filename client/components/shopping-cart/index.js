import React, { PropTypes } from 'react';
import NumberInput from 'components/number-field/number-input';
import Gridicon from 'components/gridicon';

const ShoppingCart = ( { packages, updateValue, dimensionUnit, weightUnit, errors } ) => {
	return (
		<ul>
			{ packages.map( ( pckg, pckgIndex ) => (
				<li key={ pckgIndex }>
					{ [ pckg.length, pckg.width, pckg.height ].map( dim => dim + dimensionUnit ).join( ' x ' ) }
					{ errors[ pckgIndex ] && errors[ pckgIndex ][ 'weight' ] ? <Gridicon icon="notice" /> : null }
					<NumberInput
						value={ pckg.weight }
						onChange={ ( event ) => updateValue( [ pckgIndex, 'weight' ], event.target.value ) }
						isError={ errors[ pckgIndex ] && errors[ pckgIndex ][ 'weight' ] }
					/> { weightUnit }
					<ul>
						{ pckg.items.map( ( item, itemIndex ) => (
							<li key={ itemIndex }>
								{ item.name }
								{ 1 < item.quantity ? ' (x' + item.quantity + ')' : null }
							</li>
						) ) }
					</ul>
				</li>
			) ) }
		</ul>
	);
};

ShoppingCart.propTypes = {
	packages: PropTypes.array.isRequired,
	updateValue: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object,
};

export default ShoppingCart;
