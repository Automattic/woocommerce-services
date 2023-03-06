/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import * as api from 'wcs-client/api';
import PackageRow from './package-row';
import StepContainer from '../step-container';
import { hasNonEmptyLeaves } from 'woocommerce/woocommerce-services/lib/utils/tree';
import {
	toggleStep,
	confirmCustoms,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	getFormErrors,
	isCustomsFormStepSubmitted,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import StepConfirmationButton from '../step-confirmation-button';
import getPackageDescriptions from '../packages-step/get-package-descriptions';
import { getAllPackageDefinitions } from 'woocommerce/woocommerce-services/state/packages/selectors';
import { Button, Popover } from "@wordpress/components";
import { useState } from "@wordpress/element";

const customsSummary = ( errors, translate ) => {
	if ( ! hasNonEmptyLeaves( errors ) ) {
		return translate( 'Customs information valid' );
	}

	const areAllErrorsDescriptionSpecificityErrors = errors.items && Object.values( errors.items ).every( ( item ) => {
		return 1 === Object.keys( item ).length && item.description;
	} );

	if ( areAllErrorsDescriptionSpecificityErrors ) {
		return translate( 'Descriptions are incomplete' );
	}

	return translate( 'Customs information incomplete' );
};

const CustomsStep = props => {
	const { siteId, orderId, errors, expanded, translate, isSubmitted, packageDescriptions } = props;

	const [ isEuCustomsPopoverVisible, setIsEuCustomsPopoverVisible ] = useState(
		Object.keys( window.wcsPluginData.notices ).includes( 'wcs-usps-eu-custom-description-mar-01-2023' )
	);
	const dismissNotice = () => {
		api.put( api.url.noticesDismiss(), {
			id: 'wcs-usps-eu-custom-description-mar-01-2023',
		} );
		setIsEuCustomsPopoverVisible( false );
		delete window.wcsPluginData.notices[ 'wcs-usps-eu-custom-description-mar-01-2023' ];
	};

	return (
		<StepContainer
			title={ translate( 'Customs' ) }
			summary={ isSubmitted ? customsSummary( errors, translate ) : '' }
			expanded={ expanded }
			toggleStep={ props.toggleStep }
			isSuccess={ isSubmitted && ! hasNonEmptyLeaves( errors ) }
			isError={ isSubmitted && hasNonEmptyLeaves( errors ) }
		>
			{ isEuCustomsPopoverVisible && ( <div className="eu-customs-item-description-specificity-popover-container">
				<Popover>
					<h2>{ translate( 'New requirement for EU customs' ) }</h2>

					<p>
						{ translate( 'From March 1 2023, if you are shipping to countries that follow European Union (EU) customs rules, you must provide a clear, specific description on every item.' ) }
					</p>

					<p>
						{ translate( 'For example, if you are sending clothing, you must indicate what type of clothing (e.g. men\'s shirt, girl\'s vest, boy\'s jacket) for the description to be acceptable.' ) }
					</p>

					<p>
						{ translate( 'Otherwise, shipments may be delayed or interrupted at customs.' ) }
					</p>

					<div className="eu-customs-item-description-specificity-popover-container__buttons">
						<Button isSecondary className="button" href="https://www.usps.com/international/new-eu-customs-rules.htm" target="_blank">{ translate( 'Learn more' ) }</Button>
						<Button isPrimary className="button" onClick={ dismissNotice }>{ translate( 'Got it' ) }</Button>
					</div>
				</Popover>
			</div> ) }
			{ Object.keys( packageDescriptions ).map( ( packageId, index ) => (
				<div className="customs-step__package-container" key={ packageId }>
					{ index ? <hr /> : null }
					<p className="customs-step__package-name">{ packageDescriptions[ packageId ] }</p>
					<PackageRow packageId={ packageId } siteId={ siteId } orderId={ orderId } />
				</div>
			) ) }
			<StepConfirmationButton
				disabled={ hasNonEmptyLeaves( errors ) }
				onClick={ props.confirmCustoms }
			>
				{ translate( 'Save customs form' ) }
			</StepConfirmationButton>
		</StepContainer>
	);
};

CustomsStep.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	packageDescriptions: PropTypes.objectOf( PropTypes.string ).isRequired,
	expanded: PropTypes.bool,
	isSubmitted: PropTypes.bool.isRequired,
	errors: PropTypes.object,
	toggleStep: PropTypes.func.isRequired,
	confirmCustoms: PropTypes.func.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const packages = shippingLabel.form.packages.selected;

	return {
		packageDescriptions: getPackageDescriptions(
			packages,
			getAllPackageDefinitions( state, siteId ),
			true
		),
		expanded: shippingLabel.form.customs.expanded,
		isSubmitted: isCustomsFormStepSubmitted( state, orderId, siteId ),
		errors: loaded ? getFormErrors( state, orderId, siteId ).customs : {},
	};
};

const mapDispatchToProps = ( dispatch, { orderId, siteId } ) => ( {
	toggleStep: () => dispatch( toggleStep( orderId, siteId, 'customs' ) ),
	confirmCustoms: () => dispatch( confirmCustoms( orderId, siteId ) ),
} );

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( CustomsStep ) );
