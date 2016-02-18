var React = require( 'react' );
var t = require( 'tcomb-form' );
var transform = require( 'tcomb-json-schema' );

module.exports = React.createClass( {

    onSubmit: function ( evt ) {
        evt.preventDefault();
        console.log( this.refs.form.getValue() );
    },

    render: function() {

        var FormSchema = transform( this.props.schema );

        return (
            <form onSubmit={this.onSubmit}>
                <t.form.Form ref="form" type={FormSchema} />
                <div className="form-group">
                    <button type="submit" className="btn btn-primary">Save</button>
                </div>
            </form>
        );
    }

} );