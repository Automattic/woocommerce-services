var React = require( 'react' );
var Form = require( 'tcomb-form' ).form.Form;
var transform = require( 'tcomb-json-schema' );
const _ = {
	assign: require( 'lodash/assign' ),
	transform: require( 'lodash/transform' ),
	has: require( 'lodash/has' ),
	isPlainObject: require( 'lodash/isPlainObject' )
};

module.exports = React.createClass( {

	componentWillMount: function() {
		if ( this.props.schema ) {
			this.schema = transform( this.props.schema );
		}
	},

	getInitialState: function() {
		const schema = this.props.schema || {};
		let defaults = {};

		// Pull field names and default values from the JSON Schema object
		if ( ( 'object' === schema.type ) && _.isPlainObject( schema.properties ) ) {

			defaults = _.transform( schema.properties, function( result, fieldMeta, fieldName ) {

				if ( _.has( fieldMeta, 'default' ) ) {

					result[ fieldName ] = fieldMeta.default;

				}

			} );

		}

		return {
			value: _.assign( defaults, this.props.initialValue )
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
		return this.schema ? (
			<div>
				<Form ref="form" type={ this.schema } value={ this.state.value } onChange={ this.onChange } />
				<div className="form-group">
					<button type="submit" className="btn btn-primary" onClick={ this.onSubmit }>Save Changes</button>
				</div>
			</div>
		) : null;
	}

} );

