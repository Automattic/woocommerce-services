/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import { isEmpty, isString, omit } from 'lodash';
import { Card } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
// from calypso
import { getCurrentlyOpenShippingZoneMethod } from 'woocommerce/state/ui/shipping/zones/methods/selectors';
import GlobalNotices from 'components/global-notices';
import notices from 'notices';
import { getSelectedSiteId } from 'state/ui/selectors';
import SettingsForm from 'woocommerce/woocommerce-services/views/service-settings/settings-form';
import { areShippingMethodsLoaded } from 'woocommerce/state/sites/shipping-methods/selectors';
import Button from 'components/button';
import { ProtectFormGuard } from 'lib/protect-form';
import { errorNotice, successNotice } from 'state/notices/actions';
import {
	closeShippingZoneMethod,
	openShippingZoneMethod,
} from 'woocommerce/state/ui/shipping/zones/methods/actions';
import { getCurrentlyEditingShippingZone } from 'woocommerce/state/ui/shipping/zones/selectors';
import { updateWcsShippingZoneMethod } from 'woocommerce/woocommerce-services/state/service-settings/actions';
import getFormErrors from 'woocommerce/woocommerce-services/state/service-settings/selectors/errors';
import { hasNonEmptyLeaves } from 'woocommerce/woocommerce-services/lib/utils/tree';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import SettingsGroupCard from 'woocommerce/woocommerce-services/components/settings-group-card';
import { areShippingClassesLoaded } from 'woocommerce/state/sites/shipping-classes/selectors';

class ViewWrapper extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			isSaving: false,
		};
	}

	onSave = () => {
		const { translate } = this.props;

		const successAction = ( dispatch ) => {
			this.setState( { isSaving: false } );
			dispatch( successNotice( translate( 'Your changes have been saved.' ), { duration: 4000 } ) );
		};
		const failureAction = ( dispatch, getState, { error } ) => {
			this.setState( { isSaving: false } );
			if ( error ) {
				dispatch( errorNotice( isString( error )
					? error
					: translate( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' )
				) );
			}
		};

		this.setState( { isSaving: true } );
		this.props.saveChanges( successAction, failureAction );
	};

	renderPlaceholder = () => (
		<div className="settings__placeholder">
			<SettingsGroupCard heading=" ">
				<TextField
					id="placeholder1"
					title="Lorem"
					value=""
				/>
				<TextField
					id="placeholder1"
					title="Ipsum"
					value=""
				/>
			</SettingsGroupCard>
		</div>
	);

	render() {
		const { siteId, method, translate, hasEdits, anyErrors, isLoaded } = this.props;
		return (
			<div>
				<GlobalNotices id="notices" notices={ notices.list } />
				{ method ? <SettingsForm method={ method } siteId={ siteId } /> : this.renderPlaceholder() }
				<Card className={ classNames( "settings__button-row", "card", "is-compact" ) } >
					<Button
						primary
						onClick={ this.onSave }
						busy={ this.state.isSaving }
						disabled={ this.state.isSaving || ! isLoaded || anyErrors }
					>
						{ translate( 'Save changes' ) }
					</Button>
				</Card>
				<ProtectFormGuard isChanged={ hasEdits } />
			</div>
		);
	}
}

const hasEdits = ( state ) => {
	const methodEdits = getCurrentlyEditingShippingZone( state ).methods;

	return ! isEmpty( methodEdits.currentlyEditingChanges ) ||
		! ( isEmpty( methodEdits.updates ) || isEmpty( methodEdits.updates[ 0 ] ) );
};

const saveChanges = ( successAction, failureAction ) => ( dispatch, getState ) => {
	if ( ! hasEdits( getState() ) ) {
		dispatch( successAction );
		return;
	}

	const siteId = getSelectedSiteId( getState() );
	const method = getCurrentlyOpenShippingZoneMethod( getState() );

	// Close the shipping method to trigger a full validation
	dispatch( closeShippingZoneMethod( siteId ) );
	if ( getCurrentlyOpenShippingZoneMethod( getState() ) ) {
		// If there's still a shipping method marked as "opened", it means that the full validation triggered by closing it failed
		dispatch( failureAction );
		return;
	}
	// Open the shipping method again in case the user wants to keep editing it
	dispatch( openShippingZoneMethod( siteId, method.id ) );

	dispatch(
		updateWcsShippingZoneMethod(
			siteId,
			method.id,
			method.methodType,
			omit( method, [ 'id', 'methodType', 'enabled' ] ),
			successAction,
			failureAction
		)
	);
};

export default connect(
	( state ) => {
		const siteId = getSelectedSiteId( state );

		const dataIsFullyLoaded = areShippingMethodsLoaded( state, siteId )
			&& areShippingClassesLoaded( state, siteId );

		return {
			siteId,
			isLoaded: dataIsFullyLoaded,
			method: dataIsFullyLoaded && getCurrentlyOpenShippingZoneMethod( state ),
			hasEdits: dataIsFullyLoaded && hasEdits( state ),
			anyErrors: dataIsFullyLoaded && hasNonEmptyLeaves( getFormErrors( state ) ),
		};
	},
	( dispatch ) => ( {
		saveChanges: ( ...args ) => dispatch( saveChanges( ...args ) )
	} )
)( localize( ViewWrapper ) );
