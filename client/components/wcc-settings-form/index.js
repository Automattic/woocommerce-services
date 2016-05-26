import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import isString from 'lodash/isString';
import { translate as __ } from 'lib/mixins/i18n';
import validator from 'is-my-json-valid';
import ObjectPath from 'objectpath';

const WCCSettingsForm = ( {
	storeOptions,
	schema,
	layout,
	settings,
	form,
	saveFormData,
	formActions,
	noticeActions,
	errors,
} ) => {
	const setIsSaving = ( value ) => formActions.setField( 'isSaving', value );
	const setSuccess = ( value ) => {
		formActions.setField( 'success', value );
		if ( true === value ) {
			noticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} );
		}
	};
	const setError = ( value ) => {
		formActions.setField( 'error', value );
		if ( isString( value ) ) {
			noticeActions.errorNotice( value, {
				duration: 7000,
			} );
		}
	}
	const saveForm = () => saveFormData( setIsSaving, setSuccess, setError, settings );
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					key={ idx }
					{ ...{
						group,
						schema,
						storeOptions,
						saveForm,
						form,
						errors,
					} }
				/>
			) ) }
		</div>
	);
};

WCCSettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

const getFormErrors = ( schema, data ) => {
	const validate = validator( schema, { greedy: true } );
	const success = validate( data );

	if ( ! success && validate.errors && validate.errors.length ) {
		/*
		 * Errors from `is-my-json-valid` are paths to fields, all using `data` as the root.
		 *
		 * e.g.: `data.services.first_class_parcel.adjustment
		 *
		 * This removes the `data.` prepending all errors, to facilitate easier matching to form fields.
		 */
		return validate.errors.map( ( error ) => {
			const errorPath = ObjectPath.parse( error.field );
			if ( 'data' === errorPath[0] ) {
				return errorPath.slice( 1 );
			}
			return errorPath;
		} );
	}

	return [];
};

function mapStateToProps( state, props ) {
	return {
		settings: state.settings,
		form: state.form,
		errors: getFormErrors( props.schema, state.settings ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
