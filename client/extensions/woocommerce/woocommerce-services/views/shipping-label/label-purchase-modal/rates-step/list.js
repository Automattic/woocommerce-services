/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { mapValues, find } from 'lodash';

/**
 * Internal dependencies
 */
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import Notice from 'components/notice';
import ShippingRate from './shipping-rate';
import getPackageDescriptions from '../packages-step/get-package-descriptions';

const renderRateNotice = translate => {
	return (
		<Notice
			className="rates-step__notice"
			icon="info-outline"
			showDismiss={ false }
			text={ translate(
				'The service and rate chosen by the customer at checkout is not available. Please choose another.'
			) }
		/>
	);
};

const MAX_VISIBLE_RATE = 5; // Maximum number of rates to display in the shipping label modal

class ShippingRates extends Component {
	render() {
		const packageNames = getPackageDescriptions( this.props.selectedPackages, this.props.allPackages, true );
		const hasSinglePackage = 1 === Object.keys( this.props.selectedPackages ).length;

		const shippingRates = Object.keys(this.props.selectedPackages).map((pckgId) => {
			return <PackageShippingRates
				key={pckgId}
				id={this.props.id}
				updateRate={this.props.updateRate}
				translate={this.props.translate}
				pckgId={pckgId}
				selectedRate={this.props.selectedRates[ pckgId ] || ''}  // Store owner selected rates, not customer
				availableRates={this.props.availableRates[ pckgId ]}
				packageErrors = {this.props.errors[ pckgId ] || []}
				packageNames = {packageNames[ pckgId ]}
				hasSinglePackage = { hasSinglePackage }
			/>
		});
		return <div>
			{ this.props.shouldShowRateNotice && renderRateNotice( this.props.translate ) }
			{ shippingRates }
		</div>
	}
}

class PackageShippingRates extends Component {
	constructor(props) {
		super(props);

		this.state = {
			packageRates: JSON.parse(JSON.stringify(this.props.availableRates.default.rates)),
			visiblePackageRates: []
		}

		this.state.visiblePackageRates = this.state.visiblePackageRates.concat(this.state.packageRates.splice(0, MAX_VISIBLE_RATE))
		this.handleShowMore = this.handleShowMore.bind(this);
	}

	handleShowMore() {
		this.setState({
			visiblePackageRates: this.state.visiblePackageRates.concat(this.state.packageRates.splice(0, MAX_VISIBLE_RATE))
		});
	}

	render() {
		const {
			id,
			selectedRate,
			availableRates,
			updateRate,
			translate,
			pckgId,
			hasSinglePackage,
			packageNames,
			packageErrors
		} = this.props;

		if ( ! availableRates ) {
			return null;
		}

		let signatureRates = null;
		if ( 'signature_required' in availableRates ) {
			signatureRates = availableRates.signature_required.rates || null;
		}

		const onRateUpdate = ( serviceId, signatureRequired ) => {
			return updateRate( pckgId, serviceId, signatureRequired );
		}

		return (
			<div key={ pckgId } className="rates-step__package-container">
				{ ! hasSinglePackage ? (
					<div className="rates-step__package-container-rates-header">
						{ translate( 'Choose rate: %(pckg)s', { args: { pckg: packageNames } } ) }
					</div>
				) : null }
				{ Object.values(
					// TODO: figure out why this doesn't work
					mapValues( this.state.visiblePackageRates, ( ( serviceRateObject ) => {
						const { service_id } = serviceRateObject;
						const rateObjectSignatureRequired = find( signatureRates, r => service_id === r.service_id );
						return <ShippingRate
							id={ id + '_' + pckgId }
							key={ id + '_' + pckgId + '_' + service_id }
							rateObject={ serviceRateObject }
							rateObjectSignatureRequired={ rateObjectSignatureRequired }
							updateValue={ onRateUpdate }
							isSelected={ service_id === selectedRate.serviceId }
						/>
					} ) )
				) }

				{ this.state.packageRates.length > 0 ? (
					<div
						className="rates-step__package-container-rates-show-more"
						role="button"
						onClick={ this.handleShowMore }
						onKeyDown={ this.handleShowMore }
						tabIndex="0">
					Show more rates
					</div>
				) : null }

				{ packageErrors.slice( 1 ).map( ( error, index ) => {
					// Print the rest of the errors (if any) below the dropdown
					return <FieldError type="server-error" key={ index } text={ error } />;
				} ) }
			</div>
		);
	};
}

ShippingRates.propTypes = {
	id: PropTypes.string.isRequired,
	selectedRates: PropTypes.object.isRequired,
	availableRates: PropTypes.object.isRequired,
	selectedPackages: PropTypes.object.isRequired,
	allPackages: PropTypes.object.isRequired,
	updateRate: PropTypes.func.isRequired,
	errors: PropTypes.object.isRequired,
};

export default localize( ShippingRates );
