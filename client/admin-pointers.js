/*global wcSevicesAdminPointers, ajaxurl */
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
					$( '#wcs-pointer-page-dimmer' ).fadeOut( 500 );
				}

				show_pointer( pointers, i + 1 );
			},
		} );

		target.pointer( options ).pointer( 'open' );
		if ( pointer.dim ) {
			target.css( 'z-index', 9999 );
			$( '#wcs-pointer-page-dimmer' ).fadeIn( 500 );
		}
	}
	show_pointer( wcSevicesAdminPointers, 0 );
} );
