/** @format */

/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

export const ALLOWED_TAGS = [ 'a', 'strong', 'em', 'u', 'tt', 's' ];
export const ALLOWED_ATTR = [ 'target', 'href' ];

export default html => {
	return {
		__html: DOMPurify.sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } ),
	};
};
