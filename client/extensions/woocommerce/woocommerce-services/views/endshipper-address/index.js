/** @format */

/**
 * External dependencies
 */

import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import classNames from 'classnames';
import { localize } from 'i18n-calypso';
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ExtendedHeader from 'woocommerce/components/extended-header';

import QueryEndShipperAddress from 'woocommerce/woocommerce-services/components/query-endshipper-address';
import { setFormDataValue, restorePristineSettings } from '../../state/endshipper-address/actions';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
	areSettingsErrored,
	userCanEditSettings,
	getEndShipperAddressFormMeta,
} from '../../state/endshipper-address/selectors';

class EndShipperAddress extends Component {
	componentWillUnmount() {
		this.props.restorePristineSettings( this.props.siteId );
	}

	setValue = ( key, value ) => {
		this.props.onChange();
		this.props.setFormDataValue( this.props.siteId, key, value );
	};

	renderContent = () => {
		const { isFetchError, siteId, translate } = this.props;

		if ( isFetchError ) {
			return (
				<p>{ translate( 'Unable to get your settings. Please refresh the page to try again.' ) }</p>
			);
		}

		return 'test endshipper: ' + siteId;
	};

	render() {
		const {
			formMeta,
			siteId,
			translate,
		} = this.props;

		if ( ! formMeta ) {
			return <QueryEndShipperAddress siteId={ siteId } />;
		}

		return (
			<div>
				<QueryEndShipperAddress siteId={ siteId } />
				<ExtendedHeader
					label={ translate( 'EndShipper Address' ) }
					description={ translate(
						'Set the EndShipper Store Address'
					) }
				/>
				<Card size='small' className={ classNames( 'card', 'endshipper-address__labels-container' ) }>
					{ this.renderContent() }
				</Card>
			</div>
		);
	}
}

EndShipperAddress.propTypes = {
	onChange: PropTypes.func.isRequired,
	submit: PropTypes.func,
};

function mapStateToProps( state ) {
	return {
		siteId: getSelectedSiteId( state ),
		formMeta: getEndShipperAddressFormMeta( state ),
		isFetchError: areSettingsErrored( state ),
		canEditSettings: userCanEditSettings( state ),
	};
}

function mapDispatchToProps( dispatch ) {
	return bindActionCreators(
		{
			setFormDataValue,
			restorePristineSettings,
		},
		dispatch
	);
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( EndShipperAddress ) );
