/** @format */

/**
 * External dependencies
 */

import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { find, isBoolean } from 'lodash';
import Gridicon from 'gridicons';
import { Button } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FormFieldSet from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormSelect from 'wcs-client/components/forms/form-select';
import Notice from 'components/notice';
import {
	openAddCardDialog,
	fetchSettings,
} from 'woocommerce/woocommerce-services/state/endshipper-address/actions';
import {
	areSettingsFetching,
	areSettingsLoaded,
	getMasterUserInfo,
	isPristine,
	userCanEditSettings,
} from 'woocommerce/woocommerce-services/state/endshipper-address/selectors';

class EndShipperFields extends Component {
	UNSAFE_componentWillMount() {
		this.setState( { expanded: this.isExpanded( this.props ) } );
	}

	UNSAFE_componentWillReceiveProps( props ) {
		if ( props.selectedPaymentMethod !== this.props.selectedPaymentMethod ) {
			this.setState( { expanded: this.isExpanded( props ) } );
		}
	}

	isExpanded( { pristine } ) {
		return ! pristine;
	}

	renderPlaceholder() {
		return (
			<div className= { classNames( buttonClasses, "label-settings__placeholder" ) }>
				<FormFieldSet>
					<FormLabel className="label-settings__cards-label">
						<span />
					</FormLabel>
					<FormSelect />
				</FormFieldSet>
				<FormFieldSet>
					<FormLabel className="label-settings__cards-label">
						<span />
					</FormLabel>
					<p className="label-settings__credit-card-description" />
					<Button />
				</FormFieldSet>
			</div>
		);
	}

	refetchSettings = () => {
		this.props.fetchSettings( this.props.siteId );
	};

	renderSettingsPermissionNotice = () => {
		const { canEditSettings, masterUserName, masterUserLogin, translate } = this.props;
		if ( canEditSettings ) {
			return null;
		}

		return (
			<Notice showDismiss={ false }>
				{ translate(
					'Only the site owner can change these settings. Please contact %(ownerName)s (%(ownerLogin)s)' +
						' to change the shipping label settings.',
					{
						args: {
							ownerName: masterUserName,
							ownerLogin: masterUserLogin,
						},
					}
				) }
			</Notice>
		);
	};

	onVisibilityChange = () => {
		if ( ! document.hidden ) {
			this.refetchSettings();
		}
	};

	renderContent = () => {
		const { canEditSettings, isLoading, paperSize, storeOptions, translate } = this.props;

		if ( isLoading ) {
			return this.renderPlaceholder();
		}

		const onPaperSizeChange = event => this.props.setValue( 'paper_size', event.target.value );
		const paperSizes = getPaperSizes( storeOptions.origin_country );

		return (
			<div>
				<FormFieldSet>
					{ this.renderSettingsPermissionNotice() }
					<FormLabel className="endshipper-address__cards-label">
						{ translate( 'Paper size' ) }
					</FormLabel>
					<FormSelect
						onChange={ onPaperSizeChange }
						value={ paperSize }
						disabled={ ! canEditSettings }
					>
						{ Object.keys( paperSizes ).map( size => (
							<option value={ size } key={ size }>
								{ paperSizes[ size ] }
							</option>
						) ) }
					</FormSelect>
				</FormFieldSet>
			</div>
		);
	};

	render() {
		return <div className="endshipper-address__labels-container">{ this.renderContent() }</div>;
	}
}

EndShipperFields.propTypes = {
	siteId: PropTypes.number.isRequired,
	setValue: PropTypes.func.isRequired,
};

export default connect(
	( state, { siteId } ) => {
		return {
			isLoading: areSettingsFetching( state, siteId ) && ! areSettingsLoaded( state, siteId ),
			isReloading: areSettingsFetching( state, siteId ) && areSettingsLoaded( state, siteId ),
			pristine: isPristine( state, siteId ),
			canEditSettings: userCanEditSettings( state, siteId ),
			...getMasterUserInfo( state, siteId ),
		};
	},
	dispatch =>
		bindActionCreators(
			{
				openAddCardDialog,
				fetchSettings,
			},
			dispatch
		)
)( localize( EndShipperFields ) );
