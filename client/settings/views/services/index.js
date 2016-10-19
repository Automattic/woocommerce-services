import React, { PropTypes } from 'react';
import _ from 'lodash';
import ShippingServiceGroup from './group';
import classNames from 'classnames';
import FormLegend from 'components/forms/form-legend';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const ShippingServiceGroups = ( {
	title,
	description,
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
	const serviceGroups = _.groupBy( servicesWithSettings, svc => svc.group );

	const renderServiceGroup = ( serviceGroup ) => {
		const groupFields = _.map( serviceGroups[ serviceGroup ], 'id' );
		const groupErrors = {};
		groupFields.forEach( ( fieldName ) => {
			if ( errors[ fieldName ] ) {
				groupErrors[ fieldName ] = errors[ fieldName ];
			}
		} );

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
			<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FieldDescription text={ description } />
			<div className={ classNames( 'wcc-shipping-services-groups-inner', { 'is-error': generalError } ) }>
				{ Object.keys( serviceGroups ).sort().map( renderServiceGroup ) }
			</div>
			{ generalError ? <FieldError text={ generalError } /> : null }
		</div>
	);
};

ShippingServiceGroups.propTypes = {
	title: PropTypes.string,
	description: PropTypes.string,
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
