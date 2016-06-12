import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import isString from 'lodash/isString';
import isArray from 'lodash/isArray';
import isEmpty from 'lodash/isEmpty';
import { translate as __ } from 'lib/mixins/i18n';
import validator from 'is-my-json-valid';
import ObjectPath from 'objectpath';
import * as SettingsActions from 'state/settings/actions';
import * as PackagesActions from 'state/form/packages/actions';

const WCCSettingsForm = React.createClass( {
	propTypes: {
		storeOptions: PropTypes.object.isRequired,
		schema: PropTypes.object.isRequired,
		layout: PropTypes.array.isRequired,
		saveFormData: PropTypes.func.isRequired,
	},

	componentDidMount() {
		window.addEventListener( 'beforeunload', this.warnIfChanged );
	},

	componentWillUnmount() {
		window.removeEventListener( 'beforeunload', this.warnIfChanged );
	},

	warnIfChanged( event ) {
		if ( this.props.form.pristine ) {
			return;
		}
		const text = __( 'You have unsaved changes.' );
		( event || window.event ).returnValue = text;
		return text;
	},

	setIsSaving( value ) {
		this.props.formActions.setField( 'isSaving', value );
	},

	setSuccess( value ) {
		this.props.formActions.setField( 'success', value );
		if ( true === value ) {
			this.props.noticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} );
		}
	},

	setError( value ) {
		this.props.formActions.setField( 'errors', value );

		if ( isString( value ) ) {
			this.props.noticeActions.errorNotice( value, {
				duration: 7000,
			} );
		}

		if ( isArray( value ) ) {
			this.props.noticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ), {
				duration: 7000,
			} );
		}
	},

	filterStoreOnSave( store ) {
		return store.getState().settings;
	},

	saveForm() {
		this.props.saveFormData( this.setIsSaving, this.setSuccess, this.setError, this.filterStoreOnSave );
	},

	render() {
		return (
			<div>
				<GlobalNotices id="notices" notices={ notices.list }/>
				{ this.props.layout.map( ( group, idx ) => (
					<WCCSettingsGroup
						{ ...this.props }
						group={ group }
						saveForm={ this.saveForm }
						key={ idx }
					/>
				) ) }
			</div>
		);
	},
} );

/*
 * Errors from `is-my-json-valid` are paths to fields, all using `data` as the root.
 *
 * e.g.: `data.services.first_class_parcel.adjustment
 *
 * This removes the `data.` prepending all errors, to facilitate easier matching to form fields.
 */
const removeErrorDataPathRoot = ( errantFields ) => {
	if ( isEmpty( errantFields ) || ! isArray( errantFields ) ) {
		return [];
	}

	return errantFields.map( ( field ) => {
		const errorPath = ObjectPath.parse( field );
		if ( 'data' === errorPath[ 0 ] ) {
			return errorPath.slice( 1 );
		}
		return errorPath;
	} );
};

const getFormErrors = ( schema, data ) => {
	const validate = validator( schema, { greedy: true } );
	const success = validate( data );

	if ( ! success && validate.errors && validate.errors.length ) {
		return validate.errors.map( ( error ) => error.field );
	}

	return [];
};

function mapStateToProps( state, props ) {
	return {
		settings: state.settings,
		form: state.form,
		errors: removeErrorDataPathRoot( state.form.errors || getFormErrors( props.schema, state.settings ) ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
