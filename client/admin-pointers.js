/*global wcServicesAdminPointers, ajaxurl */
/**
 * External dependencies
 */
import jQuery from 'jquery';

jQuery( document ).ready( function( $ ) {
	function show_pointer( pointers, i ) {
		if ( ! ( Array.isArray( pointers ) && pointers[ i ] ) ) {
			return;
		}

		const pointer = pointers[ i ];
		if ( 'string' !== typeof pointer.target ||
			'string' !== typeof pointer.id ||
			'object' !== typeof pointer.options ||
			'string' !== typeof pointer.options.content
		) {
			show_pointer( pointers, i + 1 );
			return;
		}

		const options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.id,
					action: 'dismiss-wp-pointer',
				} );

				if ( pointer.dim ) {
					$( '#wcs-pointer-page-dimmer' ).fadeOut( 500 );
				}

				show_pointer( pointers, i + 1 );
			},
		} );

		function open() {
			const target = $( pointer.target );
			target.pointer( options ).pointer( 'open' );
			if ( pointer.dim ) {
				target.css( 'z-index', 9999 );
				$( 'body' ).append( '<div id="wcs-pointer-page-dimmer" class="wcs-pointer-page-dimmer"></div>' );
				$( '#wcs-pointer-page-dimmer' ).fadeIn( 500 );
			}
		}

		// Selectors for delayed opening. Only show_button is required.
		if ( pointer.delayed_opening ) {
			const container = $( pointer.delayed_opening.static_container || document );
			container.one( 'click', pointer.delayed_opening.show_button, function() {
				if ( pointer.delayed_opening.dynamic_container ) {
					setTimeout( function() {
						$( pointer.delayed_opening.dynamic_container ).promise().then( open );
					}, 0 );
				} else {
					open();
				}
			} );
			if ( pointer.delayed_opening.hide_button ) {
				container.one( 'click', pointer.delayed_opening.hide_button, function() {
					$( pointer.target ).pointer( 'close' );
				} );
			}
		} else {
			open();
		}
	}
	show_pointer( wcServicesAdminPointers, 0 );
} );
