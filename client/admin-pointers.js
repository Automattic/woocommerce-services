/*global wcSevicesAdminPointers, ajaxurl */
/**
 * External dependencies
 */
import jQuery from 'jquery';

jQuery( document ).ready( function( $ ) {
	function show_pointer( pointers, i ) {
		if ( ! Array.isArray( pointers ) ||
			! pointers[ i ]
		) {
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
				show_pointer( pointers, i + 1 );
			},
		} );

		$( pointer.target ).pointer( options ).pointer( 'open' );
	}
	show_pointer( wcSevicesAdminPointers, 0 );
} );
