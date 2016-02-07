var React = require( 'react' );
var Form = require( 'react-json-editor' );

module.exports = React.createClass( {

    render: function() {
        return (
            <Form schema={this.props.schema} />
        );
    }

} );