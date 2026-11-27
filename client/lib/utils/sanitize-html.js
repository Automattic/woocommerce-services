/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

export default ( html ) => {
	return {
		__html: DOMPurify.sanitize( html,
			{
				ALLOWED_TAGS: [
					'a',
					'strong',
					'em',
					'u',
					'tt',
					's',
				],
				ALLOWED_ATTR: [
					'target',
					'href',
				],
			}
		),
	};
};
