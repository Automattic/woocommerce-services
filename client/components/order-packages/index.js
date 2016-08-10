import React, { PropTypes } from 'react';
import NumberInput from 'components/number-field/number-input';
import Gridicon from 'components/gridicon';
import { sanitize } from 'dompurify';

const renderItemInfo = ( item, itemIndex ) => (
	<li key={ itemIndex }>
		<span dangerouslySetInnerHTML={ { __html: sanitize( item.name ) } } />
		{ 1 < item.quantity ? ' (x' + item.quantity + ')' : null }
	</li>
);

const renderPackageDimensions = ( pckg, dimensionUnit ) => {
	return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit}`;
};

const OrderPackages = ( { packages, updateValue, dimensionUnit, weightUnit, errors } ) => {
	const renderPackageInfo = ( pckg, pckgIndex ) => {
		const pckgErrors = errors[ pckgIndex ] || {};
		return (
			<li key={ pckgIndex }>
				{ renderPackageDimensions( pckg, dimensionUnit ) }
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

OrderPackages.propTypes = {
	packages: PropTypes.array.isRequired,
	updateValue: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object,
};

export default OrderPackages;

export const Summary = ( { packages, dimensionUnit, weightUnit } ) => {
	const renderPackageInfo = ( pckg, pckgIndex ) => {
		const dimensions = renderPackageDimensions( pckg, dimensionUnit );
		return (
			<li key={ pckgIndex }>
				{ `${dimensions} (${pckg.weight} ${weightUnit})` }
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

Summary.propTypes = {
	packages: PropTypes.array.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
};
