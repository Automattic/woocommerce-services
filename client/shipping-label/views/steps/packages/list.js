import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dropdown from 'components/dropdown';
import NumberField from 'components/number-field';
import FormButton from 'components/forms/form-button';

const renderPackageDimensions = ( pckg, dimensionUnit ) => {
	return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit}`;
};

const getPackages = ( dimensionUnit ) => {
	const packages = [
		{ id: 'bike', length: 1, width: 2, height: 3, name: __( 'Bike Box' ) },
		{ id: 'bike2', length: 12, width: 12, height: 13, name: __( 'Bike Box2' ) },
	];

	const result = {};
	packages.forEach( ( pckg ) => {
		result[ pckg.id ] = pckg.name + ': ' + renderPackageDimensions( pckg, dimensionUnit );
	} );

	return result;
};

const OrderPackages = ( props ) => {
	const { packages, dimensionUnit, weightUnit, errors } = props;
	console.dir( props );
	const renderPackageInfo = ( pckg, pckgIndex ) => {
		const pckgErrors = errors[ pckgIndex ] || {};
		return (
			<div key={ `package_${pckgIndex}` }>
				<div> { __( 'Package' ) } { pckgIndex + 1 } { __( 'of' ) } { packages.length }</div>
				<Dropdown
					id={ `package_select_${pckgIndex}` }
					title={ __( 'Choose a package' ) }
					description={ __( 'Go to the packaging manager to add or edit a saved package.' ) }
					valuesMap={ getPackages( dimensionUnit ) }
					value={ pckg.package_id || '' }
					updateValue={ () => {} }
					error={ pckgErrors.package }
				/>

				<NumberField
					id={ `weight_${pckgIndex}` }
					title={ __( 'Total Weight' ) }
					value={ pckg.weight }
					updateValue={ () => {} }
					error={ pckgErrors.weight }
				/>

				<div>
					{ weightUnit }
				</div>

				<div className="address__confirmation-container">
					<FormButton type="button" disabled={ true } isPrimary >
						{ __( 'Weigh using scale' ) }
					</FormButton>
				</div>

			</div>
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
	updateWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.array.isRequired,
};

export default OrderPackages;
