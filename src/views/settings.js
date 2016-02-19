var React = require( 'react' );
var Form = require( 'tcomb-form' ).form.Form;
var transform = require( 'tcomb-json-schema' );

module.exports = React.createClass( {

    componentWillMount: function() {

        if ( this.props.schema ) {
            this.schema = transform( this.props.schema );
        }

    },

    onSubmit: function( evt ) {

        if ( ! this.refs.form.getValue() ) {
            evt.preventDefault();
        }

    },

    render: function() {

        return this.schema ? (
            <div>
                <Form ref="form" type={this.schema} />
                <div className="form-group">
                    <button type="submit" className="btn btn-primary" onClick={this.onSubmit}>Save Changes</button>
                </div>
            </div>
        ) : null;

    }

} );