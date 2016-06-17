import React, { PropTypes } from 'react';
import groupBy from 'lodash/groupBy';
import ShippingServiceGroup from './group';
import map from 'lodash/map';
import classNames from 'classnames';
import FormInputValidation from 'components/forms/form-input-validation';

const renderFieldError = ( validationHint ) => {
	return (
		<FormInputValidation isError text={ validationHint } />
	);
};

const ShippingServiceGroups = ( {
	services,
	settings,
	currencySymbol,
	updateValue,
	settingsKey,
	errors,
	generalError,
} ) => {
	// Some shippers have so many services that it is helpful to organize them
	// into groups.  This code iterates over the services and extracts the group(s)
	// it finds.  When rendering, we can then iterate over the group(s).
	const servicesWithSettings = services.map( svc => Object.assign( {}, svc, settings[ svc.id ] ) );
	const serviceGroups = groupBy( servicesWithSettings, svc => svc.group );

	const renderServiceGroup = ( serviceGroup ) => {
		const groupFields = map( serviceGroups[ serviceGroup ], 'id' );
		const groupErrors = errors.filter( ( error ) => ( -1 < groupFields.indexOf( error[ 0 ] ) ) );

		return (
			<ShippingServiceGroup
				key={ serviceGroup }
				title={ serviceGroups[ serviceGroup ][ 0 ].group_name }
				services={ serviceGroups[ serviceGroup ] }
				currencySymbol={ currencySymbol }
				updateValue={ updateValue }
				settingsKey={ settingsKey }
				errors={ groupErrors }
			/>
		);
	};

	return (
		<div className="wcc-shipping-services-groups">
			<div className={ classNames( 'wcc-shipping-services-groups-inner', { 'is-error': generalError } ) }>
				{ Object.keys( serviceGroups ).sort().map( renderServiceGroup ) }
			</div>
			{ generalError ? renderFieldError( generalError ) : null }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	services: PropTypes.array.isRequired,
	settings: PropTypes.object.isRequired,
	currencySymbol: PropTypes.string,
	updateValue: PropTypes.func.isRequired,
	settingsKey: PropTypes.string.isRequired,
};

ShippingServiceGroups.defaultProps = {
	currencySymbol: '$',
	settings: {},
};

export default ShippingServiceGroups;
