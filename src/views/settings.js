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

        evt.preventDefault();

        var formValue = this.refs.form.getValue();

        if ( formValue ) {
            console.log( 'valid! we can send to server.', formValue );
        }

    },

    render: function() {

        return this.schema ? (
            <div>
                <Form ref="form" type={this.schema} />
                <div className="form-group">
                    <button type="submit" className="btn btn-primary">Save Changes</button>
                </div>
            </div>
        ) : null;

    }

} );