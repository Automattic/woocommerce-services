var React = require( 'react' );
var Form = require( 'tcomb-form' ).form.Form;
var transform = require( 'tcomb-json-schema' );

module.exports = React.createClass( {

    componentWillMount: function() {

        if ( this.props.schema ) {
            this.schema = transform( this.props.schema );
        }

    },

    getInitialState: function() {

        return {
            value: this.props.initialValue || {}
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

        // TODO: Add submit button back here once it is working correctly
        // TODO: and remove it from the container when WC core allows that

        return this.schema ? (
            <div>
                <Form ref="form" type={this.schema} value={this.state.value} onChange={this.onChange} />
            </div>
        ) : null;

    }

} );