var React = require( 'react' );
var ReactDOM = require( 'react-dom' );
var Settings = require( './views/settings' );

var schema = {
    "type": "object",
    "title": "USPS",
    "description": "The USPS extension obtains rates dynamically from the USPS API during cart/checkout.",
    "required": [],
    "properties": {
        "enabed": {
            "type"       : "boolean",
            "title"      : " Enable/Disable",
            "description": "Enable this shipping method",
            "default"    : false
        },
        "title" : {
            "type"       : "string",
            "title"      : "Method Title",
            "description": "This controls the title which the user sees during checkout.",
            "default"    : ""
        }
    }
};

document.addEventListener( "DOMContentLoaded", function( event ) {
    ReactDOM.render(
        React.createElement( Settings, { schema: schema } ), document.getElementById( 'wc-connect-admin-container' )
    );
} );