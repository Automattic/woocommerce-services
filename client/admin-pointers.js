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

		const target = $( pointer.target );

		const options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.id,
					action: 'dismiss-wp-pointer',
				} );

				if ( pointer.dim ) {
					$( '#wcs-pointer-page-dimmer' ).fadeOut( 500, () => $( pointer.target ).css( 'z-index', '' ) );
				} else {
					$( pointer.target ).css( 'z-index', '' );
				}

				show_pointer( pointers, i + 1 );
			},
		} );

		function open() {
			target.pointer( options ).pointer( 'open' );
			if ( pointer.dim ) {
				target.css( 'z-index', 9999 );
				$( 'body' ).append( '<div id="wcs-pointer-page-dimmer" class="wcs-pointer-page-dimmer"></div>' );
				$( '#wcs-pointer-page-dimmer' ).fadeIn( 500 );
			}
		}

		/**
		 * Selectors for delayed opening
		 * - show_button: element to trigger pointer open on click (required)
		 * - hide_button: if specified, element to trigger pointer close on click
		 * - animating_container: if specified, queues opening of pointer after element's animation completes
		 * - delegation_container: if specified, scopes event delegation to this parent element
		 */
		if ( pointer.delayed_opening ) {
			const container = $( pointer.delayed_opening.delegation_container || document );
			container.one( 'click', pointer.delayed_opening.show_button, function() {
				if ( pointer.delayed_opening.animating_container ) {
					setTimeout( function() {
						$( pointer.delayed_opening.animating_container ).promise().then( open );
					}, 0 );
				} else {
					open();
				}
			} );
			if ( pointer.delayed_opening.hide_button ) {
				container.one( 'click', pointer.delayed_opening.hide_button, function() {
					target.pointer( 'close' );
				} );
			}
		} else {
			open();
		}
	}
	show_pointer( wcServicesAdminPointers, 0 );
} );
