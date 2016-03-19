import React from 'react';
import { form as tcomb } from 'tcomb-form';
import schemaToType from 'tcomb-json-schema';
import { assign, transform, has, isPlainObject } from 'lodash';

module.exports = React.createClass( {

	componentWillMount: function() {
		if ( this.props.schema ) {
			this.type = schemaToType( this.props.schema );
		}
	},

	getInitialState: function() {
		const schema = this.props.schema || {};
		let defaults = {};

		// Pull field names and default values from the JSON Schema object
		if ( ( 'object' === schema.type ) && isPlainObject( schema.properties ) ) {
			defaults = transform( schema.properties, ( result, fieldMeta, fieldName ) => {
				if ( has( fieldMeta, 'default' ) ) {
					result[ fieldName ] = fieldMeta.default;
				}
			} );
		}

		return {
			value: assign( defaults, this.props.initialValue )
		};
	},

	onChange: function( newValue ) {
		this.setState( {
			value: newValue
		} );
	},

	onSubmit: function( event ) {
		// getValue() returns null if validation fails
		// See: https://github.com/gcanti/tcomb-form/blob/master/GUIDE.md#getvalue
		if ( ! this.refs.form.getValue() ) {
			event.preventDefault();
		}
	},

	render: function() {
		return this.type ? (
			<div>
				<tcomb.Form ref="form" type={ this.type } value={ this.state.value } onChange={ this.onChange } />
				<div className="form-group">
					<button type="submit" className="btn btn-primary" onClick={ this.onSubmit }>Save Changes</button>
				</div>
			</div>
		) : null;
	}

} );

