import React, { PropTypes } from 'react';
import NumberInput from 'components/number-field/number-input';
import Gridicon from 'components/gridicon';

const ShoppingCart = ( { packages, updateValue, dimensionUnit, weightUnit, errors } ) => {
	const renderItemInfo = ( item, itemIndex ) => (
		<li key={ itemIndex }>
			{ item.name }
			{ 1 < item.quantity ? ' (x' + item.quantity + ')' : null }
		</li>
	);

	const renderPackageDimensions = ( pckg ) => {
		return [ pckg.length, pckg.width, pckg.height ].map( dim => dim + dimensionUnit ).join( ' x ' );
	};

	const renderPackageInfo = ( pckg, pckgIndex ) => {
		const pckgErrors = errors[ pckgIndex ] || {};
		return (
			<li key={ pckgIndex }>
				{ renderPackageDimensions( pckg ) }

				{ pckgErrors.weight ? <Gridicon icon="notice" /> : null }
				<NumberInput
					value={ pckg.weight }
					onChange={ ( event ) => updateValue( [ pckgIndex, 'weight' ], event.target.value ) }
					isError={ Boolean( pckgErrors.weight ) }
					style={ { width: 60, marginLeft: 16 } }
				/> { weightUnit }

				<ul>
					{ pckg.items.map( renderItemInfo ) }
				</ul>
			</li>
		);
	};

	return (
		<ul>
			{ packages.map( renderPackageInfo ) }
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
