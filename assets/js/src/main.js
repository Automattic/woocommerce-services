var React = require( 'react' );
var ReactDOM = require( 'react-dom' );
var Settings = require( './views/settings.jsx' );

jQuery( document ).ready( function( $ ) {

    ReactDOM.render(
        React.createElement( Settings, {} ), document.getElementById( 'wc-connect-admin-container' )
    );

} );